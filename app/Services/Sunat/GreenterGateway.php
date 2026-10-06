<?php

namespace App\Services\Sunat;

use App\Models\CompanySunatSetting;
use App\Models\CreditNote;
use App\Models\Order;
use App\Services\Sunat\Contracts\SunatGateway;
use App\Support\NumberToWords;
use Closure;
use DateTime;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Envía boletas, facturas y notas de crédito directo a SUNAT con la librería Greenter (sin proveedor).
 */
class GreenterGateway implements SunatGateway
{
    /**
     * Credenciales públicas del ambiente de pruebas de SUNAT (pautas oficiales del servicio BETA):
     * usuario = RUC + MODDATOS y clave = MODDATOS (distingue mayúsculas).
     */
    private const BETA_USER     = 'MODDATOS';
    private const BETA_PASSWORD = 'MODDATOS';

    public function __construct(private CertificateService $certificates)
    {
    }

    public function send(Order $order): SunatResult
    {
        return $this->transmit(
            $order->company_id,
            fn (CompanySunatSetting $setting) => $this->buildInvoice($order, $setting),
            "la orden {$order->id}"
        );
    }

    public function sendCreditNote(CreditNote $note): SunatResult
    {
        return $this->transmit(
            $note->company_id,
            fn (CompanySunatSetting $setting) => $this->buildNote($note, $setting),
            "la nota de crédito {$note->id}"
        );
    }

    /**
     * Arma el documento, lo firma, lo envía a SUNAT e interpreta la respuesta.
     * Nunca lanza excepciones: los fallos se devuelven como resultado "error".
     *
     * @param  Closure(CompanySunatSetting): DocumentInterface  $builder
     */
    private function transmit(int $companyId, Closure $builder, string $label): SunatResult
    {
        try {
            $setting = CompanySunatSetting::with('company')->where('company_id', $companyId)->first();

            if (!$setting || !$setting->isReady()) {
                return new SunatResult(Order::SUNAT_ERROR, 'CONFIG', 'La facturación electrónica de la compañía no está configurada completa.');
            }

            $see    = $this->buildSee($setting);
            $result = $see->send($builder($setting));
            $xml    = $see->getFactory()->getLastXml();
            $hash   = $this->digest($xml);

            if ($result->isSuccess()) {
                $cdr = $result->getCdrResponse();

                // SUNAT recibió el comprobante: 0 = aceptado (4000+ = aceptado con observaciones)
                if ($cdr && $cdr->isAccepted()) {
                    return new SunatResult(Order::SUNAT_ACCEPTED, $cdr->getCode(), $cdr->getDescription(), $hash, $xml, $result->getCdrZip());
                }

                return new SunatResult(Order::SUNAT_REJECTED, $cdr?->getCode(), $cdr?->getDescription(), $hash, $xml, $result->getCdrZip());
            }

            $error = $result->getError();
            $code  = (string) $error?->getCode();

            // 2000-3999: SUNAT rechazó el comprobante (hay que corregirlo).
            // Cualquier otro código es una falla de recepción o de conexión: se puede reintentar.
            $rejected = ctype_digit($code) && (int) $code >= 2000 && (int) $code <= 3999;

            return new SunatResult(
                $rejected ? Order::SUNAT_REJECTED : Order::SUNAT_ERROR,
                $code ?: null,
                $error?->getMessage() ?? 'SUNAT no respondió.',
                $hash,
                $xml
            );
        } catch (\Throwable $e) {
            Log::error("Error al enviar {$label} a SUNAT: " . $e->getMessage());

            return new SunatResult(Order::SUNAT_ERROR, 'EXCEPTION', $e->getMessage());
        }
    }

    /**
     * Configura Greenter: certificado, claves SOL y servicio (pruebas o producción).
     */
    private function buildSee(CompanySunatSetting $setting): See
    {
        $isBeta = $setting->isBeta();

        $see = new See();
        $see->setCachePath(null);
        $see->setCertificate($this->pem($setting));
        $see->setService($isBeta ? SunatEndpoints::FE_BETA : SunatEndpoints::FE_PRODUCCION);

        // En pruebas siempre se usan las credenciales públicas de SUNAT; en producción, las de la empresa
        $see->setClaveSOL(
            $setting->company->ruc,
            $isBeta ? self::BETA_USER : $setting->sol_user,
            $isBeta ? self::BETA_PASSWORD : $setting->sol_password
        );

        return $see;
    }

    /**
     * Greenter firma con un PEM (certificado + clave privada); el .pfx guardado se convierte aquí.
     */
    private function pem(CompanySunatSetting $setting): string
    {
        // Pruebas: certificado autofirmado incluido en el sistema (SUNAT beta no exige uno registrado)
        if ($setting->isBeta()) {
            $pem = file_get_contents(resource_path('sunat/beta-certificate.pem'));

            return substr($pem, strpos($pem, '-----BEGIN'));
        }

        $certs = [];

        if (!openssl_pkcs12_read($this->certificates->read($setting), $certs, $setting->certificate_password)) {
            throw new InvalidArgumentException('No se pudo leer el certificado digital guardado.');
        }

        return $certs['cert'] . $certs['pkey'];
    }

    /**
     * Arma la factura (01) o boleta (03) con los importes ya calculados y guardados en la orden.
     */
    private function buildInvoice(Order $order, CompanySunatSetting $setting): Invoice
    {
        $order->loadMissing(['items', 'branch', 'documentType']);

        [$series, $number] = explode('-', $order->voucher_number);

        $invoice = (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')                                  // venta interna
            ->setTipoDoc((int) $order->voucher_type === 2 ? '01' : '03') // 01 factura · 03 boleta
            ->setSerie($series)
            ->setCorrelativo((string) (int) $number)
            ->setFechaEmision(new DateTime($order->created_at->format('Y-m-d\TH:i:sP')))
            ->setFormaPago(new FormaPagoContado())
            ->setTipoMoneda('PEN')
            ->setCompany($this->company($order, $setting))
            ->setClient($this->client($order))
            ->setMtoOperGravadas((float) $order->taxable_amount)
            ->setMtoOperExoneradas((float) $order->exonerated_amount)
            ->setMtoOperInafectas((float) $order->unaffected_amount)
            ->setMtoIGV((float) $order->igv)
            ->setTotalImpuestos((float) $order->igv)
            ->setValorVenta((float) $order->subtotal)
            ->setSubTotal((float) $order->total)
            ->setMtoImpVenta((float) $order->total);

        $invoice->setDetails($order->items->map(fn ($item) => $this->detail($item))->all());

        $invoice->setLegends([
            (new Legend())->setCode('1000')->setValue(NumberToWords::soles((float) $order->total)),
        ]);

        return $invoice;
    }

    /**
     * Arma la nota de crédito (07) que anula por completo la venta original:
     * mismos importes y mismo detalle, referenciando el comprobante afectado.
     */
    private function buildNote(CreditNote $note, CompanySunatSetting $setting): Note
    {
        $note->loadMissing(['order.items', 'order.branch', 'order.documentType']);
        $order = $note->order;

        [$series, $number]               = explode('-', $note->voucher_number);
        [$affectedSeries, $affectedNumber] = explode('-', $order->voucher_number);

        $credit = (new Note())
            ->setUblVersion('2.1')
            ->setTipoDoc('07')
            ->setSerie($series)
            ->setCorrelativo((string) (int) $number)
            ->setFechaEmision(new DateTime($note->created_at->format('Y-m-d\TH:i:sP')))
            ->setTipDocAfectado((int) $order->voucher_type === 2 ? '01' : '03')       // comprobante que se anula
            ->setNumDocfectado($affectedSeries . '-' . (int) $affectedNumber)
            ->setCodMotivo($note->reason_code)
            ->setDesMotivo($note->reason_text)
            ->setTipoMoneda('PEN')
            ->setCompany($this->company($order, $setting))
            ->setClient($this->client($order))
            ->setMtoOperGravadas((float) $note->taxable_amount)
            ->setMtoOperExoneradas((float) $note->exonerated_amount)
            ->setMtoOperInafectas((float) $note->unaffected_amount)
            ->setMtoIGV((float) $note->igv)
            ->setTotalImpuestos((float) $note->igv)
            ->setValorVenta((float) $note->subtotal)
            ->setSubTotal((float) $note->total)
            ->setMtoImpVenta((float) $note->total);

        $credit->setDetails($order->items->map(fn ($item) => $this->detail($item))->all());

        $credit->setLegends([
            (new Legend())->setCode('1000')->setValue(NumberToWords::soles((float) $note->total)),
        ]);

        return $credit;
    }

    private function company(Order $order, CompanySunatSetting $setting): Company
    {
        return (new Company())
            ->setRuc($setting->company->ruc)
            ->setRazonSocial($setting->legal_name)
            ->setNombreComercial($setting->trade_name ?: $setting->legal_name)
            ->setAddress(
                (new Address())
                    ->setUbigueo($setting->ubigeo)
                    ->setDepartamento($setting->department)
                    ->setProvincia($setting->province)
                    ->setDistrito($setting->district)
                    ->setUrbanizacion('-')
                    ->setDireccion($setting->fiscal_address)
                    ->setCodLocal($order->branch?->sunat_establishment_code ?? '0000')
            );
    }

    private function client(Order $order): Client
    {
        $hasDocument = filled($order->customer_document) && $order->documentType;

        return (new Client())
            ->setTipoDoc($hasDocument ? $order->documentType->code : '0')
            ->setNumDoc($hasDocument ? $order->customer_document : '0')
            ->setRznSocial($order->customer_name ?: 'CLIENTES VARIOS');
    }

    private function detail($item): SaleDetail
    {
        $base     = (float) $item->subtotal;
        $igv      = (float) $item->igv_amount;
        $quantity = (float) $item->quantity;
        $taxed    = $item->igv_affectation === TaxCalculator::GRAVADO;

        return (new SaleDetail())
            ->setCodProducto($item->product_code)
            ->setUnidad($item->unit_code ?: 'NIU')
            ->setCantidad($quantity)
            ->setDescripcion($item->product_name)
            ->setMtoValorUnitario(round($base / $quantity, 6))          // valor unitario sin IGV
            ->setMtoBaseIgv($base)
            ->setPorcentajeIgv($taxed ? TaxCalculator::IGV_RATE * 100 : 0)
            ->setIgv($igv)
            ->setTipAfeIgv((string) $item->igv_affectation)              // 10 gravado · 20 exonerado · 30 inafecto
            ->setTotalImpuestos($igv)
            ->setMtoValorVenta($base)
            ->setMtoPrecioUnitario(round(($base + $igv) / $quantity, 6)); // precio unitario con IGV
    }

    /**
     * Resumen (DigestValue) de la firma digital del XML.
     */
    private function digest(?string $xml): ?string
    {
        if ($xml && preg_match('/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/', $xml, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}

<?php

namespace App\Services\Sunat;

use App\Models\CompanySunatSetting;
use App\Models\Order;
use App\Services\Sunat\Contracts\SunatGateway;
use App\Services\Sunat\TaxCalculator;
use App\Support\NumberToWords;
use DateTime;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Envía boletas y facturas directo a SUNAT con la librería Greenter (sin proveedor).
 */
class GreenterGateway implements SunatGateway
{
    /** Credenciales públicas del ambiente de pruebas de SUNAT (si no hay otras guardadas) */
    private const BETA_USER     = 'MODDATOS';
    private const BETA_PASSWORD = 'moddatos';

    public function __construct(private CertificateService $certificates)
    {
    }

    public function send(Order $order): SunatResult
    {
        try {
            $setting = CompanySunatSetting::with('company')->where('company_id', $order->company_id)->first();

            if (!$setting || !$setting->isReady()) {
                return new SunatResult(Order::SUNAT_ERROR, 'CONFIG', 'La facturación electrónica de la compañía no está configurada completa.');
            }

            $see     = $this->buildSee($setting);
            $invoice = $this->buildInvoice($order, $setting);

            $result = $see->send($invoice);
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
            Log::error("Error al enviar la orden {$order->id} a SUNAT: " . $e->getMessage());

            return new SunatResult(Order::SUNAT_ERROR, 'EXCEPTION', $e->getMessage());
        }
    }

    /**
     * Configura Greenter: certificado, claves SOL y servicio (pruebas o producción).
     */
    private function buildSee(CompanySunatSetting $setting): See
    {
        $isBeta = $setting->environment !== CompanySunatSetting::ENV_PRODUCTION;

        $see = new See();
        $see->setCachePath(null);
        $see->setCertificate($this->pem($setting));
        $see->setService($isBeta ? SunatEndpoints::FE_BETA : SunatEndpoints::FE_PRODUCCION);
        $see->setClaveSOL(
            $setting->company->ruc,
            $setting->sol_user ?: ($isBeta ? self::BETA_USER : ''),
            $setting->sol_password ?: ($isBeta ? self::BETA_PASSWORD : '')
        );

        return $see;
    }

    /**
     * Greenter firma con un PEM (certificado + clave privada); el .pfx guardado se convierte aquí.
     */
    private function pem(CompanySunatSetting $setting): string
    {
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

<?php

namespace App\Services\Sunat\Contracts;

use App\Models\CreditNote;
use App\Models\Order;
use App\Models\SunatSummary;
use App\Services\Sunat\SunatResult;
use Illuminate\Support\Collection;

/**
 * Canal de envío de comprobantes a SUNAT.
 * Hoy lo implementa Greenter (directo a SUNAT); si más adelante se contrata un
 * proveedor (PSE/OSE), solo se agrega otra implementación y se cambia el binding.
 */
interface SunatGateway
{
    /**
     * Envía una boleta o factura a SUNAT y devuelve el resultado.
     * No lanza excepciones por rechazos ni por fallas de conexión: las devuelve como
     * SunatResult con estado "rejected" o "error".
     */
    public function send(Order $order): SunatResult;

    /**
     * Envía una nota de crédito (anulación total de una boleta o factura) a SUNAT.
     * Mismas reglas de resultado que send().
     */
    public function sendCreditNote(CreditNote $note): SunatResult;

    /**
     * Envía un resumen diario de boletas. SUNAT lo procesa aparte: si lo recibe, el resultado
     * trae el ticket (estado "pending") y el veredicto se consulta luego con summaryStatus().
     * Las fallas de conexión se devuelven como estado "error", sin lanzar excepciones.
     *
     * @param  Collection<int, Order>  $orders  boletas del resumen, en el orden de las líneas
     */
    public function sendSummary(SunatSummary $summary, Collection $orders): SunatResult;

    /**
     * Firma cada boleta (sin enviarla) y devuelve su resumen de firma (DigestValue) por id de orden.
     * En modo resumen diario la boleta no viaja sola, pero el QR del ticket necesita su propio hash.
     * Si una boleta no se puede firmar, simplemente no aparece en el resultado.
     *
     * @param  Collection<int, Order>  $orders
     * @return array<int, string>
     */
    public function digestOrders(Collection $orders): array;

    /**
     * Consulta con el ticket el resultado de un resumen ya recibido por SUNAT.
     * Estado "pending" = SUNAT aún lo procesa; "accepted" o "rejected" = veredicto final.
     */
    public function summaryStatus(SunatSummary $summary): SunatResult;
}

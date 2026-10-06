<?php

namespace App\Services\Sunat\Contracts;

use App\Models\CreditNote;
use App\Models\Order;
use App\Services\Sunat\SunatResult;

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
}

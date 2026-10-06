<?php

namespace App\Services\Sunat;

use App\Models\Order;

/**
 * Resultado del envío de un comprobante a SUNAT.
 */
class SunatResult
{
    public function __construct(
        /** Estado resultante para la orden (Order::SUNAT_ACCEPTED, SUNAT_REJECTED o SUNAT_ERROR) */
        public readonly string $status,
        /** Código de respuesta de SUNAT (0 = aceptado) o del error */
        public readonly ?string $code = null,
        /** Descripción de SUNAT o del error */
        public readonly ?string $message = null,
        /** Resumen (DigestValue) del XML firmado */
        public readonly ?string $hash = null,
        /** XML firmado enviado a SUNAT */
        public readonly ?string $xml = null,
        /** Constancia de recepción (CDR) en formato ZIP */
        public readonly ?string $cdrZip = null,
    ) {
    }

    public function accepted(): bool
    {
        return $this->status === Order::SUNAT_ACCEPTED;
    }
}

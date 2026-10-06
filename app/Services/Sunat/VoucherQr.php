<?php

namespace App\Services\Sunat;

use App\Models\Order;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Código QR de la representación impresa de una boleta o factura (formato SUNAT).
 * Se genera en el servidor como SVG: no necesita internet ni imagenes externas.
 */
class VoucherQr
{
    /**
     * Contenido del QR:
     * RUC | TIPO (01 factura, 03 boleta) | SERIE | NÚMERO | IGV | TOTAL | FECHA | TIPO DOC. CLIENTE | NÚM. DOC. CLIENTE |
     */
    public function text(Order $order): string
    {
        $order->loadMissing(['company', 'documentType']);

        [$series, $number] = array_pad(explode('-', (string) $order->voucher_number, 2), 2, '');

        $hasDocument = filled($order->customer_document) && $order->documentType;

        return implode('|', [
            $order->company->ruc,
            (int) $order->voucher_type === 2 ? '01' : '03',
            $series,
            $number,
            number_format((float) $order->igv, 2, '.', ''),
            number_format((float) $order->total, 2, '.', ''),
            $order->created_at->format('Y-m-d'),
            $hasDocument ? $order->documentType->code : '0',
            $hasDocument ? $order->customer_document : '0',
        ]) . '|';
    }

    /**
     * QR en SVG listo para incrustar en la vista (sin la cabecera XML).
     */
    public function svg(Order $order, int $size = 200): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd()));

        $svg = $writer->writeString($this->text($order));

        return trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }
}

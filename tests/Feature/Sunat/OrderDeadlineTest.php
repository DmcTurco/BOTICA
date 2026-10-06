<?php

use App\Models\Order;
use Illuminate\Support\Carbon;

// Plazos para informar a SUNAT: factura 3 días calendario, boleta 7

function pendingOrder(int $voucherType, string $createdAt, string $status = Order::SUNAT_PENDING): Order
{
    $order = new Order(['voucher_type' => $voucherType, 'sunat_status' => $status]);
    $order->created_at = Carbon::parse($createdAt);

    return $order;
}

afterEach(fn () => Carbon::setTestNow());

it('da 3 días a la factura y 7 a la boleta', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    expect(pendingOrder(2, '2026-10-10 09:00:00')->sunatDaysLeft())->toBe(3)
        ->and(pendingOrder(1, '2026-10-10 09:00:00')->sunatDaysLeft())->toBe(7);
});

it('marca vencido cuando pasó el plazo', function () {
    Carbon::setTestNow('2026-10-14 08:00:00');

    expect(pendingOrder(2, '2026-10-10 09:00:00')->sunatDaysLeft())->toBe(-1);
});

it('vence hoy el último día del plazo', function () {
    Carbon::setTestNow('2026-10-13 23:00:00');

    expect(pendingOrder(2, '2026-10-10 09:00:00')->sunatDaysLeft())->toBe(0);
});

it('no calcula plazo para comprobantes ya aceptados ni para notas de venta', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    expect(pendingOrder(2, '2026-10-10 09:00:00', Order::SUNAT_ACCEPTED)->sunatDaysLeft())->toBeNull()
        ->and(pendingOrder(3, '2026-10-10 09:00:00')->sunatDaysLeft())->toBeNull();
});

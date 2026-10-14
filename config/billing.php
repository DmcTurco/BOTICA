<?php

/*
|--------------------------------------------------------------------------
| Cobro de la mensualidad del servicio
|--------------------------------------------------------------------------
| Cada empresa paga un monto mensual (companies.monthly_fee) y tiene el servicio
| pagado hasta una fecha (companies.paid_until). El administrador registra los
| pagos a mano (Yape, transferencia...) y la fecha se extiende sola.
*/

return [

    // Días antes del vencimiento en que empieza el aviso ámbar
    'warning_days' => (int) env('BILLING_WARNING_DAYS', 5),

    // Días después del vencimiento con aviso rojo antes de bloquear el acceso
    'grace_days' => (int) env('BILLING_GRACE_DAYS', 5),

    // Instrucciones de pago que ven las boticas en el aviso y en la pantalla de bloqueo
    'payment_info' => env('BILLING_PAYMENT_INFO', 'Comunícate con el administrador del sistema para registrar tu pago.'),

];

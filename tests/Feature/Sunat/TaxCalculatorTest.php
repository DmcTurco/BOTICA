<?php

use App\Services\Sunat\TaxCalculator;

// Los precios son SIN IGV: el 18% se suma solo a lo gravado

it('suma el IGV solo a las líneas gravadas', function () {
    $tax = (new TaxCalculator())->calculate(
        [
            ['code' => 'A', 'price' => 10, 'qty' => 2],   // gravado: base 20, igv 3.60
            ['code' => 'B', 'price' => 5,  'qty' => 1],   // exonerado
            ['code' => 'C', 'price' => 3,  'qty' => 1],   // inafecto
        ],
        ['A' => '10', 'B' => '20', 'C' => '30']
    );

    expect($tax['taxable'])->toBe(20.0)
        ->and($tax['exonerated'])->toBe(5.0)
        ->and($tax['unaffected'])->toBe(3.0)
        ->and($tax['subtotal'])->toBe(28.0)
        ->and($tax['igv'])->toBe(3.6)
        ->and($tax['total'])->toBe(31.6);
});

it('redondea el IGV por línea y luego suma', function () {
    // 3 líneas de 0.05 gravadas: IGV por línea = 0.009 → 0.01 cada una = 0.03 (no 0.027 → 0.03 por casualidad)
    $tax = (new TaxCalculator())->calculate(
        [
            ['code' => 'A', 'price' => 0.05, 'qty' => 1],
            ['code' => 'A', 'price' => 0.05, 'qty' => 1],
            ['code' => 'A', 'price' => 0.05, 'qty' => 1],
        ],
        ['A' => '10']
    );

    expect($tax['igv'])->toBe(0.03)->and($tax['total'])->toBe(0.18);
});

it('rechaza un producto sin afectación de IGV', function () {
    (new TaxCalculator())->calculate([['code' => 'X', 'price' => 1, 'qty' => 1]], []);
})->throws(InvalidArgumentException::class);

it('rechaza una afectación que no es 10, 20 ni 30', function () {
    (new TaxCalculator())->calculate([['code' => 'X', 'price' => 1, 'qty' => 1]], ['X' => '99']);
})->throws(InvalidArgumentException::class);

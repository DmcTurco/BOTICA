<?php

use App\Services\Sunat\TaxCalculator;

// Los precios YA INCLUYEN el IGV: en lo gravado se separa (base = precio ÷ 1.18); en exonerado e inafecto no se saca nada

it('separa el IGV solo de las líneas gravadas y el total es lo que se cobra', function () {
    $tax = (new TaxCalculator())->calculate(
        [
            ['product_id' => 'A', 'price' => 10, 'qty' => 2],   // gravado: 20 con IGV → base 16.95 + igv 3.05
            ['product_id' => 'B', 'price' => 5,  'qty' => 1],   // exonerado
            ['product_id' => 'C', 'price' => 3,  'qty' => 1],   // inafecto
        ],
        ['A' => '10', 'B' => '20', 'C' => '30']
    );

    expect($tax['taxable'])->toBe(16.95)
        ->and($tax['exonerated'])->toBe(5.0)
        ->and($tax['unaffected'])->toBe(3.0)
        ->and($tax['subtotal'])->toBe(24.95)
        ->and($tax['igv'])->toBe(3.05)
        ->and($tax['total'])->toBe(28.0);   // 20 + 5 + 3: el IGV no se suma, ya estaba dentro
});

it('redondea el IGV por línea y luego suma', function () {
    // 3 líneas de 0.05 gravadas: base = 0.05 ÷ 1.18 = 0.04 e IGV = 0.01 en cada una
    $tax = (new TaxCalculator())->calculate(
        [
            ['product_id' => 'A', 'price' => 0.05, 'qty' => 1],
            ['product_id' => 'A', 'price' => 0.05, 'qty' => 1],
            ['product_id' => 'A', 'price' => 0.05, 'qty' => 1],
        ],
        ['A' => '10']
    );

    expect($tax['igv'])->toBe(0.03)->and($tax['taxable'])->toBe(0.12)->and($tax['total'])->toBe(0.15);
});

it('rechaza un producto sin afectación de IGV', function () {
    (new TaxCalculator())->calculate([['product_id' => 'X', 'price' => 1, 'qty' => 1]], []);
})->throws(InvalidArgumentException::class);

it('rechaza una afectación que no es 10, 20 ni 30', function () {
    (new TaxCalculator())->calculate([['product_id' => 'X', 'price' => 1, 'qty' => 1]], ['X' => '99']);
})->throws(InvalidArgumentException::class);

it('un producto exonerado de 10 cobra 10 sin IGV y uno gravado de 10 cobra 10 con el IGV adentro', function () {
    $calc = new TaxCalculator();

    $exonerado = $calc->calculate([['product_id' => 'A', 'price' => 10, 'qty' => 1]], ['A' => '20']);
    expect($exonerado['total'])->toBe(10.0)->and($exonerado['igv'])->toBe(0.0)->and($exonerado['subtotal'])->toBe(10.0);

    $gravado = $calc->calculate([['product_id' => 'A', 'price' => 10, 'qty' => 1]], ['A' => '10']);
    expect($gravado['total'])->toBe(10.0)->and($gravado['subtotal'])->toBe(8.47)->and($gravado['igv'])->toBe(1.53);
});

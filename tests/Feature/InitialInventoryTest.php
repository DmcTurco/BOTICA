<?php

use App\Models\BranchStock;
use App\Models\Employee;
use App\Models\PriceChange;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

it('fija el stock contado con su costo, precio, lote y vencimiento, y solo toca las filas llenas', function () {
    loginEmployee();
    stockProduct('P1', 0, 0);
    stockProduct('P2', 0, 0);

    $this->get(route('employee.initial-inventory.index'))->assertOk()->assertSee('Producto P1')->assertSee('Guardar conteo');

    $this->post(route('employee.initial-inventory.store'), ['rows' => [
        'P1' => ['quantity' => 50, 'cost' => 2, 'price' => 5, 'batch' => 'L-77', 'expiration' => today()->addYear()->toDateString()],
        'P2' => ['quantity' => '', 'cost' => '', 'price' => ''],          // sin llenar: no se toca
    ]])->assertSessionHas('success');

    $p1 = Product::find('P1');
    expect(stockOf('P1'))->toBe(50.0)
        ->and(stockOf('P2'))->toBe(0.0)
        ->and((float) $p1->purchase_price)->toBe(2.0)
        ->and((float) $p1->unit_sale_price)->toBe(5.0)
        ->and(PriceChange::where('product_code', 'P1')->count())->toBe(2)
        ->and(StockBatch::where('product_code', 'P1')->value('batch'))->toBe('L-77');

    // Queda en el kardex como ajuste "Inventario inicial" (aparece también en Ajuste de inventario)
    $mov = StockMovement::where('product_code', 'P1')->sole();
    expect($mov->reference_type)->toBe('manual')
        ->and($mov->notes)->toContain('Inventario inicial')
        ->and((float) $mov->quantity)->toBe(50.0);
});

it('la cantidad contada fija el stock (no suma): si hay menos, lo baja', function () {
    loginEmployee();
    stockProduct('P1', 10, 1);

    $this->post(route('employee.initial-inventory.store'), ['rows' => ['P1' => ['quantity' => 4]]])->assertSessionHas('success');
    expect(stockOf('P1'))->toBe(4.0);

    // Contar lo mismo que ya hay no mueve el stock
    $this->post(route('employee.initial-inventory.store'), ['rows' => ['P1' => ['quantity' => 4]]]);
    expect(StockMovement::where('product_code', 'P1')->count())->toBe(1);
});

it('valida los datos y avisa si no hay nada que guardar', function () {
    loginEmployee();
    stockProduct('P1', 0);

    $this->post(route('employee.initial-inventory.store'), ['rows' => ['P1' => ['quantity' => -3]]])->assertSessionHasErrors('rows.P1.quantity');
    $this->post(route('employee.initial-inventory.store'), ['rows' => ['P1' => ['quantity' => '']]])->assertSessionHas('error');

    expect(stockOf('P1'))->toBe(0.0);
});

it('exige el permiso de ajuste de inventario', function () {
    loginEmployee([Employee::PRIV_VER_VENTAS]);

    $this->get(route('employee.initial-inventory.index'))->assertRedirect(route('employee.home'));
});

it('la plantilla trae los productos del filtro con su stock y precios', function () {
    loginEmployee();
    stockProduct('P1', 3, 2);

    $csv = $this->get(route('employee.initial-inventory.template'))->assertOk()->streamedContent();

    expect($csv)->toContain('codigo,producto,laboratorio,stock_actual,cantidad_contada,costo_unitario,precio_venta,lote,vencimiento')
        ->and($csv)->toContain('P1,"Producto P1"');
});

it('importa la plantilla en CSV con el formato de Excel en español (punto y coma, coma decimal, dd/mm/aaaa)', function () {
    loginEmployee();
    stockProduct('P1', 0, 0);
    stockProduct('P2', 0, 0);

    $csv = "\xEF\xBB\xBFcodigo;producto;laboratorio;stock_actual;cantidad_contada;costo_unitario;precio_venta;lote;vencimiento\n"
         . "P1;Producto P1;;0;12;0,50;1,20;L-1;31/12/2027\n"
         . "P2;Producto P2;;0;abc;;;;\n"                 // número inválido: se informa y no se guarda
         . "P9;No existe;;0;5;;;;\n"                      // producto que no es de la compañía
         . "P1;repetido sin cantidad;;0;;;;;\n";          // sin nada que hacer: se ignora

    $this->post(route('employee.initial-inventory.import'), [
        'file' => UploadedFile::fake()->createWithContent('conteo.csv', $csv),
    ])->assertSessionHas('error')->assertSessionHasErrors();

    expect(stockOf('P1'))->toBe(12.0)
        ->and((float) Product::find('P1')->purchase_price)->toBe(0.5)
        ->and((float) Product::find('P1')->unit_sale_price)->toBe(1.2)
        ->and(StockBatch::where('product_code', 'P1')->first()->expiration_date->toDateString())->toBe('2027-12-31')
        ->and(stockOf('P2'))->toBe(0.0);

    $errors = session('errors')->all();
    expect(implode(' ', $errors))->toContain('abc')->toContain('P9');
});

it('importa la plantilla guardada como Excel (.xlsx), con fechas seriales', function () {
    loginEmployee();
    stockProduct('P1', 0, 0);

    // .xlsx mínimo: encabezado y una fila (vencimiento como número de serie de Excel: 46752 = 31/12/2027)
    $rows = [
        ['codigo', 'producto', 'laboratorio', 'stock_actual', 'cantidad_contada', 'costo_unitario', 'precio_venta', 'lote', 'vencimiento'],
        ['P1', 'Producto P1', '', 0, 30, 0.4, 1, 'X-9', 46752],
    ];
    $path = tempnam(sys_get_temp_dir(), 'inv') . '.xlsx';
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE);
    $xml = '';
    foreach ($rows as $r => $row) {
        $xml .= '<row r="' . ($r + 1) . '">';
        foreach ($row as $c => $v) {
            $ref = chr(65 + $c) . ($r + 1);
            $xml .= is_numeric($v) ? "<c r=\"{$ref}\"><v>{$v}</v></c>" : "<c r=\"{$ref}\" t=\"inlineStr\"><is><t>{$v}</t></is></c>";
        }
        $xml .= '</row>';
    }
    $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $xml . '</sheetData></worksheet>');
    $zip->close();

    $this->post(route('employee.initial-inventory.import'), [
        'file' => new UploadedFile($path, 'conteo.xlsx', null, null, true),
    ])->assertSessionHas('success');

    expect(stockOf('P1'))->toBe(30.0)
        ->and(StockBatch::where('product_code', 'P1')->first(['batch', 'expiration_date'])->batch)->toBe('X-9')
        ->and(StockBatch::where('product_code', 'P1')->first()->expiration_date->toDateString())->toBe('2027-12-31');

    @unlink($path);
});

it('rechaza un archivo que no es la plantilla', function () {
    loginEmployee();

    $this->post(route('employee.initial-inventory.import'), [
        'file' => UploadedFile::fake()->createWithContent('otra.csv', "nombre,precio\nX,1\n"),
    ])->assertSessionHas('error', fn ($m) => str_contains($m, 'columnas de la plantilla'));
});

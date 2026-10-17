<?php

use App\Models\Admin;
use App\Models\BranchStock;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\Presentation;
use App\Models\Product;
use App\Services\CompanyProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Alta de una compañía desde el panel admin, con o sin el catálogo de productos */
function createCompanyWithCatalog(bool $loadProducts): Company
{
    test()->actingAs(Admin::first(), 'admin');
    test()->post(route('admin.companies.store'), [
        'name' => 'Botica Catálogo', 'email' => 'catalogo@example.com',
        'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        'branch_name' => 'Sede Principal', 'load_products' => $loadProducts ? '1' : '0',
    ])->assertSessionHasNoErrors();

    return Company::where('email', 'catalogo@example.com')->firstOrFail();
}

it('al crear la compañía con la casilla marcada, nace con el catálogo de productos en stock 0', function () {
    $company = createCompanyWithCatalog(true);

    $products = Product::where('company_id', $company->id);
    expect($products->count())->toBe(CompanyProductCatalog::size())
        ->and(CompanyProductCatalog::size())->toBeGreaterThan(3000)
        // Stock 0: no hay filas de stock (se crean con la primera compra o ajuste)
        ->and(BranchStock::whereIn('product_code', $products->pluck('code'))->count())->toBe(0)
        // Códigos de 8 dígitos, cargados por el sistema (sin empleado)
        ->and((clone $products)->orderBy('code')->value('code'))->toMatch('/^P-\d{8}$/')
        ->and((clone $products)->whereNotNull('employee_id')->count())->toBe(0);

    // Un medicamento común trae laboratorio, registro sanitario, principio activo, precio y su caja
    $med = (clone $products)->whereNotNull('sanitary_registry')->where('unit_sale_price', '>', 0)
        ->whereHas('presentations')->with(['laboratory', 'presentations', 'unit', 'category'])->firstOrFail();
    $caja = $med->presentations->first();
    expect($med->laboratory)->not->toBeNull()
        ->and($med->active_ingredient)->not->toBeEmpty()
        ->and($med->unit)->not->toBeNull()
        ->and($med->category)->not->toBeNull()
        ->and((float) $caja->sale_price)->toBe(round((float) $med->unit_sale_price * (float) $caja->equivalent_amount, 2));

    // Los no medicamentos vienen sin precio (la botica pone el suyo)
    expect((clone $products)->where('name', 'Termómetro digital')->value('unit_sale_price'))->toEqual(0);

    // Los laboratorios del catálogo base se reutilizan (no se duplica "Bayer" ni "Medifarma")
    expect($company->laboratories()->where('name', 'like', '%Bayer%')->count())->toBe(1)
        ->and($company->laboratories()->where('name', 'like', '%Medifarma%')->count())->toBe(1);
});

it('sin la casilla, la compañía nace solo con el catálogo base (sin productos)', function () {
    $company = createCompanyWithCatalog(false);

    expect(Product::where('company_id', $company->id)->count())->toBe(0)
        ->and($company->units()->count())->toBe(12)
        ->and($company->categories()->count())->toBe(12);
});

it('el formulario de alta muestra la casilla marcada con la cantidad de productos', function () {
    $this->actingAs(Admin::first(), 'admin');

    $this->get(route('admin.companies.create'))->assertOk()
        ->assertSee('Cargar el catálogo de productos (stock en 0)')
        ->assertSee(number_format(CompanyProductCatalog::size()))
        ->assertSee('name="load_products" value="1" class', false);
});

it('no deja vender un producto sin precio de venta', function () {
    $employee = loginEmployee();
    posSetup($employee);
    Product::where('code', 'P1')->update(['unit_sale_price' => 0]);

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 0, 'qty' => 1]], 'total' => 0])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => '«Producto P1» no tiene precio de venta. Ponle su precio en Productos antes de venderlo.']);
});

it('reserva códigos en bloque sin chocar con los que se piden uno a uno', function () {
    DB::beginTransaction();
    $one   = DocumentSeries::siguiente(DocumentSeries::PRODUCTO);
    $block = DocumentSeries::reservar(DocumentSeries::PRODUCTO, 3);
    $next  = DocumentSeries::siguiente(DocumentSeries::PRODUCTO);
    DB::commit();

    expect($one)->toBe('P-00000001')
        ->and($block)->toBe(['P-00000002', 'P-00000003', 'P-00000004'])
        ->and($next)->toBe('P-00000005');
});

it('el comando arma el catálogo desde el Excel de DIGEMID', function () {
    // Excel mínimo con el formato de DIGEMID: títulos arriba y el encabezado en la fila 3
    $header = ['N°', 'Código de Producto', 'Nombre Comercial', 'Concentración', 'Forma Farmacéutica', 'Presentación', 'Fracciones',
        'Clasificación', 'Registro Sanitario', 'Titular de Registro Sanitario', 'Fabricante', 'País', 'Grupo producto', 'IFA',
        'Cantidad IFAS', 'Cantidad de establecimientos reportantes por producto', 'media', 'mediana', 'moda'];
    $rows = [
        ['INDICADOR DE PRECIOS'], [],
        $header,
        [1, 100, 'PARACETAMOL', '500 MG', 'TABLETA', 'CAJA ENVASE BLISTER', 100, 'GENERICO', 'EN00001', 'MEDIFARMA S.A.', 'MEDIFARMA S.A.', 'PERU', 'ESPECIALIDAD FARMACEUTICA', 'PARACETAMOL', 1, 5000, 0.12, 0.10, 0.10],
        [2, 200, 'TOSALBRON ® ADULTO', '50 MG / 5 ML', 'JARABE', 'FRASCO X 120 ML', 1, 'MARCA', 'EN00002', 'VITALINE SOCIEDAD ANONIMA CERRADA', 'X', 'PERU', 'ESPECIALIDAD FARMACEUTICA', 'CITRATO DE OXOLAMINA', 1, 2000, 17, 17.5, 17],
        [3, 300, 'RARO', '1 MG', 'TABLETA', 'CAJA', 10, 'MARCA', 'EN00003', 'LAB S.A.C.', 'X', 'PERU', 'ESPECIALIDAD FARMACEUTICA', 'XYZ', 1, 5, 1, 1, 1],
    ];
    $xlsx = tempnam(sys_get_temp_dir(), 'mtc') . '.xlsx';
    $zip = new ZipArchive();
    $zip->open($xlsx, ZipArchive::CREATE);
    $cells = '';
    foreach ($rows as $r => $row) {
        $cells .= '<row r="' . ($r + 1) . '">';
        foreach (array_values($row) as $c => $value) {
            $ref = chr(65 + $c % 26);
            $ref = ($c >= 26 ? chr(64 + intdiv($c, 26)) : '') . $ref . ($r + 1);
            $cells .= is_numeric($value)
                ? "<c r=\"{$ref}\"><v>{$value}</v></c>"
                : "<c r=\"{$ref}\" t=\"inlineStr\"><is><t>" . htmlspecialchars($value) . '</t></is></c>';
        }
        $cells .= '</row>';
    }
    $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $cells . '</sheetData></worksheet>');
    $zip->close();

    $output = base_path(\App\Console\Commands\BuildDigemidCatalog::OUTPUT);
    $backup = file_get_contents($output);

    try {
        $this->artisan('catalogo:digemid', ['archivo' => $xlsx, '--min' => 1000])->assertSuccessful();

        $csv = array_map('str_getcsv', file($output, FILE_IGNORE_NEW_LINES));
        $data = array_map(fn ($row) => array_combine($csv[0], $row), array_slice($csv, 1));

        expect($data)->toHaveCount(2)                                  // "Raro" lo reportan solo 5 boticas
            ->and($data[0]['name'])->toBe('Paracetamol 500 mg Tableta')
            ->and($data[0]['laboratory'])->toBe('Medifarma')
            ->and($data[0]['unit'])->toBe('Tableta')
            ->and($data[0]['pack_amount'])->toBe('100')
            ->and($data[0]['unit_price'])->toBe('0.10')
            ->and($data[0]['category'])->toBe('Analgésicos y Antipiréticos')
            ->and($data[1]['name'])->toBe('Tosalbron Adulto 50 mg / 5 ml Jarabe')
            ->and($data[1]['laboratory'])->toBe('Vitaline')
            ->and($data[1]['unit'])->toBe('Frasco')
            ->and($data[1]['pack_amount'])->toBe('');
    } finally {
        file_put_contents($output, $backup);   // el catálogo real del proyecto queda intacto
        @unlink($xlsx);
    }
});

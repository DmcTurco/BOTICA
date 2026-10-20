<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockBatch;
use App\Services\ClientProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const IMPORT_HEADER = 'codigo_barras,producto,categoria,laboratorio,principio_activo,stock,precio_compra,precio_venta,stock_minimo,stock_maximo,vencimiento,requiere_receta,cantidad_caja,precio_caja,cantidad_blister,precio_blister';

/** Compañía recién creada desde el panel (con su primera sede) y sin productos */
function importTarget(string $email = 'importa@example.com', string $name = 'Botica Importada'): Branch
{
    test()->actingAs(Admin::first(), 'admin');
    test()->post(route('admin.companies.store'), [
        'name' => $name, 'email' => $email, 'password' => 'secreto123',
        'password_confirmation' => 'secreto123', 'branch_name' => 'Sede Principal', 'load_products' => '0',
    ])->assertSessionHasNoErrors();

    return Company::where('email', $email)->firstOrFail()->branches()->firstOrFail();
}

function csvUpload(array $lines, string $name = 'productos.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, implode("\n", array_merge([IMPORT_HEADER], $lines)) . "\n");
}

/** Sube el archivo (sin elegir compañía) y devuelve la respuesta de la subida */
function uploadImport(UploadedFile $file)
{
    test()->actingAs(Admin::first(), 'admin');

    return test()->post(route('admin.client-imports.preview'), ['file' => $file]);
}

/** Sube el archivo y abre su vista de revisión; con $branch la abre con ese destino elegido */
function previewImport(?Branch $branch, UploadedFile $file)
{
    $upload = uploadImport($file);
    $upload->assertRedirect();
    $url = $upload->headers->get('Location');

    return test()->get($branch ? $url . '?branch_id=' . $branch->id : $url);
}

/** Token de la vista de revisión, leído del formulario de registro */
function importToken($response): string
{
    preg_match('/name="token" value="([A-Za-z0-9]{40})"/', $response->getContent(), $m);

    return $m[1] ?? '';
}

afterEach(fn () => Storage::disk('local')->deleteDirectory('client-imports'));

it('descarga el modelo con los encabezados y una fila de ejemplo', function () {
    $this->actingAs(Admin::first(), 'admin');

    $csv = $this->get(route('admin.client-imports.template'))->assertOk()->streamedContent();

    expect($csv)->toContain(IMPORT_HEADER)->toContain('EJEMPLO');
});

it('solo el admin entra a la importación', function () {
    $this->get(route('admin.client-imports.create'))->assertRedirect();
    $this->post(route('admin.client-imports.preview'))->assertRedirect();
    $this->get(route('admin.client-imports.show', str_repeat('a', 40)))->assertRedirect();
});

it('la pantalla de subida pide solo el archivo, sin elegir compañía', function () {
    $this->actingAs(Admin::first(), 'admin');

    $this->get(route('admin.client-imports.create'))->assertOk()
        ->assertSee('Descargar modelo')->assertSee('Subir y revisar')
        ->assertDontSee('name="branch_id"', false);
});

it('se sube el archivo, se revisa completo y la compañía se elige al final', function () {
    $branch = importTarget();

    $response = previewImport(null, csvUpload([
        '7750001,Paracetamol 500 mg,Analgésicos,Medifarma,Paracetamol,24,0.20,0.50,3,10,31/12/2027,No,100,40,10,4',
        ',Alcohol 70 120 ml,Antisépticos,,,0,2.00,3.50,,,,No,,,,',
        // la fila de ejemplo del modelo se ignora
        '7750000000011,EJEMPLO - Paracetamol (borra esta fila),X,X,X,5,1,2,,,,No,,,,',
    ]))->assertOk()
        ->assertSee('Revisa lo que subiste')->assertSee('Paracetamol 500 mg')
        ->assertSee('Elige la compañía y sede…')          // el destino se elige en esta vista
        ->assertSee('Botica Importada — Sede Principal');

    // Solo se vio: no hay productos todavía
    expect(Product::where('company_id', $branch->company_id)->count())->toBe(0);

    $this->post(route('admin.client-imports.store'), ['token' => importToken($response), 'branch_id' => $branch->id])
        ->assertRedirect(route('admin.companies.edit', $branch->company_id))
        ->assertSessionHas('success');

    expect(Product::where('company_id', $branch->company_id)->count())->toBe(2);

    $para = Product::where('barcode', '7750001')->firstOrFail();
    expect((float) BranchStock::where('branch_id', $branch->id)->where('product_id', $para->id)->value('stock_actual'))->toBe(24.0)
        ->and($para->presentations()->count())->toBe(3)
        ->and($para->laboratory->name)->toBe('Medifarma')
        ->and(StockBatch::where('product_id', $para->id)->first()->expiration_date->toDateString())->toBe('2027-12-31');

    // Alcohol: sin código de barras ni stock mínimo/máximo (quedan vacíos, no en 0)
    $alcohol = Product::where('name', 'Alcohol 70 120 ml')->firstOrFail();
    $stock = BranchStock::where('product_id', $alcohol->id)->firstOrFail();
    expect($alcohol->barcode)->toBeNull()->and($stock->stock_minimum)->toBeNull()->and($stock->stock_maximum)->toBeNull();

    // La revisión se consume: no se puede registrar dos veces con el mismo token
    $this->post(route('admin.client-imports.store'), ['token' => importToken($response), 'branch_id' => $branch->id])->assertRedirect(route('admin.client-imports.create'));
    expect(Product::where('company_id', $branch->company_id)->count())->toBe(2);
});

it('la vista de revisión se puede volver a abrir y vence si ya se registró', function () {
    $branch = importTarget();
    $response = previewImport(null, csvUpload(['7750001,Uno,Cat,,,5,1,2,,,,No,,,,']));
    $token = importToken($response);

    $this->get(route('admin.client-imports.show', $token))->assertOk()->assertSee('Uno');
    $this->post(route('admin.client-imports.store'), ['token' => $token, 'branch_id' => $branch->id])->assertSessionHas('success');
    $this->get(route('admin.client-imports.show', $token))->assertRedirect(route('admin.client-imports.create'));
});

it('registrar exige elegir la compañía y sede', function () {
    importTarget();
    $token = importToken(previewImport(null, csvUpload(['7750001,Uno,Cat,,,5,1,2,,,,No,,,,'])));

    $this->post(route('admin.client-imports.store'), ['token' => $token])->assertSessionHasErrors('branch_id');
    $this->post(route('admin.client-imports.store'), ['token' => $token, 'branch_id' => 999999])->assertSessionHasErrors('branch_id');
});

it('con errores muestra todos y no deja registrar ni guarda nada', function () {
    $branch = importTarget();

    $response = previewImport(null, csvUpload([
        '7750001,Bueno,Cat,,,5,1,2,,,,No,,,,',
        '7.75E+12,Código en notación científica,Cat,,,5,1,2,,,,No,,,,',
        '7750003,Stock negativo,Cat,,,-3,1,2,,,,No,,,,',
        '7750004,Fecha mala,Cat,,,5,1,2,,,32/13/2027,No,,,,',
        '7750001,Barras repetido,Cat,,,5,1,2,,,,No,,,,',
        '7750006,Caja sin precio,Cat,,,5,1,2,,,,No,100,,,',
        ',,Cat,,,5,1,2,,,,No,,,,',
    ]))->assertOk();

    $response->assertSee('impiden importar')
        ->assertSee('7.75E+12')->assertSee('no es un número válido en stock')
        ->assertSee('no es una fecha válida')->assertSee('ya está en la fila 2')
        ->assertSee('necesita cantidad y precio a la vez')->assertSee('falta el nombre del producto')
        ->assertDontSee('name="token"', false);

    expect(Product::where('company_id', $branch->company_id)->count())->toBe(0);

    // Aunque alguien intente registrarlo con el token, el servidor lo rechaza
    $upload = uploadImport(csvUpload(['7750001,Bueno,Cat,,,5,1,2,,,,No,,,,', ',,Cat,,,5,1,2,,,,No,,,,']));
    $token = basename($upload->headers->get('Location'));
    $this->post(route('admin.client-imports.store'), ['token' => $token, 'branch_id' => $branch->id])->assertSessionHas('error');
    expect(Product::where('company_id', $branch->company_id)->count())->toBe(0);
});

it('al elegir una compañía que ya tiene esos códigos de barras lo avisa y no deja registrar', function () {
    $branch = importTarget();
    $first = previewImport(null, csvUpload(['7750001,Primero,Cat,,,5,1,2,,,,No,,,,']));
    $this->post(route('admin.client-imports.store'), ['token' => importToken($first), 'branch_id' => $branch->id])->assertSessionHas('success');

    // En otra compañía el mismo archivo no tiene problema
    $other = importTarget('otra@example.com', 'Otra Botica');
    previewImport($other, csvUpload(['7750001,Otra vez,Cat,,,5,1,2,,,,No,,,,']))->assertDontSee('ya tiene 1 de estos códigos');

    // En la que ya los tiene: aviso en la revisión y el registro se rechaza
    $response = previewImport($branch, csvUpload(['7750001,Otra vez,Cat,,,5,1,2,,,,No,,,,']))
        ->assertSee('ya tiene 1 de estos códigos de barras')->assertSee('ya existe en esta compañía');

    $this->post(route('admin.client-imports.store'), ['token' => importToken($response), 'branch_id' => $branch->id, 'accept_existing' => '1'])
        ->assertSessionHas('error');
    expect(Product::where('company_id', $branch->company_id)->count())->toBe(1);
});

it('si la compañía elegida ya tiene productos pide aceptar que se agregan como nuevos', function () {
    $branch = importTarget();
    $first = previewImport(null, csvUpload(['7750001,Primero,Cat,,,5,1,2,,,,No,,,,']));
    $this->post(route('admin.client-imports.store'), ['token' => importToken($first), 'branch_id' => $branch->id]);

    $second = previewImport($branch, csvUpload(['7750002,Segundo,Cat,,,5,1,2,,,,No,,,,']))
        ->assertSee('accept_existing', false);
    $token = importToken($second);

    $this->post(route('admin.client-imports.store'), ['token' => $token, 'branch_id' => $branch->id])->assertSessionHas('error');
    expect(Product::where('company_id', $branch->company_id)->count())->toBe(1);

    $this->post(route('admin.client-imports.store'), ['token' => $token, 'branch_id' => $branch->id, 'accept_existing' => '1'])->assertSessionHas('success');
    expect(Product::where('company_id', $branch->company_id)->count())->toBe(2);
});

it('lee un CSV de Excel en Windows: punto y coma, tildes en Windows-1252 y precios con coma', function () {
    $branch = importTarget();
    $content = mb_convert_encoding(
        "codigo_barras;producto;categoria;stock;precio_compra;precio_venta\n7750001;Ácido fólico;Vitaminas;12;0,25;0,60\n",
        'Windows-1252', 'UTF-8'
    );

    $response = previewImport(null, UploadedFile::fake()->createWithContent('cliente.csv', $content))->assertOk()->assertSee('Revisa lo que subiste');
    $this->post(route('admin.client-imports.store'), ['token' => importToken($response), 'branch_id' => $branch->id])->assertSessionHas('success');

    $product = Product::where('barcode', '7750001')->firstOrFail();
    expect($product->name)->toBe('Ácido fólico')->and((float) $product->purchase_price)->toBe(0.25)->and((float) $product->unit_sale_price)->toBe(0.6);
});

it('lee un Excel (.xlsx) aunque tenga celdas vacías', function () {
    $branch = importTarget();

    // Encabezado y una fila: la columna "laboratorio" (D) queda sin celda, como las guarda Excel
    $rows = [
        ['A' => 'codigo_barras', 'B' => 'producto', 'C' => 'categoria', 'D' => 'laboratorio', 'E' => 'stock', 'F' => 'precio_compra', 'G' => 'precio_venta', 'H' => 'vencimiento'],
        ['A' => '7750009', 'B' => 'Ibuprofeno 400 mg', 'C' => 'Antiinflamatorios', 'E' => 10, 'F' => 0.3, 'G' => 0.8, 'H' => 47118],   // 47118 = 31/12/2028
    ];
    $xml = '';
    foreach ($rows as $r => $cells) {
        $xml .= '<row r="' . ($r + 1) . '">';
        foreach ($cells as $col => $value) {
            $xml .= is_numeric($value)
                ? "<c r=\"{$col}" . ($r + 1) . "\"><v>{$value}</v></c>"
                : "<c r=\"{$col}" . ($r + 1) . "\" t=\"inlineStr\"><is><t>" . htmlspecialchars($value) . '</t></is></c>';
        }
        $xml .= '</row>';
    }
    $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $xml . '</sheetData></worksheet>');
    $zip->close();

    $response = previewImport(null, new UploadedFile($path, 'cliente.xlsx', null, null, true))->assertOk()->assertSee('Ibuprofeno 400 mg');
    $this->post(route('admin.client-imports.store'), ['token' => importToken($response), 'branch_id' => $branch->id])->assertSessionHas('success');

    $product = Product::where('barcode', '7750009')->firstOrFail();
    expect($product->laboratory_id)->toBeNull()
        ->and((float) BranchStock::where('product_id', $product->id)->value('stock_actual'))->toBe(10.0)
        ->and(StockBatch::where('product_id', $product->id)->first()->expiration_date->toDateString())->toBe('2028-12-31');
    @unlink($path);
});

it('un archivo sin la columna producto o sin precios se rechaza con un mensaje claro', function () {
    uploadImport(UploadedFile::fake()->createWithContent('malo.csv', "nombre_raro,otra\nx,y\n"))
        ->assertRedirect()->assertSessionHas('error');

    uploadImport(UploadedFile::fake()->createWithContent('sin_precios.csv', "producto,stock\nX,1\n"))
        ->assertRedirect()->assertSessionHas('error', fn ($m) => str_contains($m, 'precio_compra'));
});

it('un archivo que no es Excel ni CSV se rechaza', function () {
    uploadImport(UploadedFile::fake()->create('productos.pdf', 10))->assertSessionHasErrors('file');
});

it('el servicio avisa de productos sin precio de venta sin bloquear', function () {
    $file   = csvUpload(['7750001,Sin precio,Cat,,,5,1,0,,,,No,,,,']);   // se guarda en una variable: si no, PHP borra el temporal
    $result = ClientProductImport::parse($file->getRealPath(), 'csv');

    expect($result['errors'])->toBe([])->and($result['rows'])->toHaveCount(1)
        ->and($result['warnings'][0])->toContain('no tiene precio de venta');
});

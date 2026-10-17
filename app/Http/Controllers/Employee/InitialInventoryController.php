<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\InitialInventoryImportRequest;
use App\Http\Requests\Company\InitialInventoryRequest;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Laboratory;
use App\Models\Product;
use App\Services\InitialInventoryService;
use App\Support\CsvExport;
use App\Support\XlsxReader;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Inventario inicial (toma de inventario): contar el stock que ya hay en el estante para muchos
 * productos a la vez, por pantalla o con una plantilla de Excel. Es por sede (la del empleado).
 */
class InitialInventoryController extends Controller
{
    /** Columnas de la plantilla (el orden importa solo al descargar; al importar se leen por nombre) */
    private const TEMPLATE = ['codigo', 'producto', 'laboratorio', 'stock_actual', 'cantidad_contada', 'costo_unitario', 'precio_venta', 'lote', 'vencimiento'];

    /**
     * Productos de la compañía con su stock en la sede, para contar página por página.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $products = $this->filtered($request, $employee)
            ->with(['laboratory:id,name', 'unit:id,abbreviation'])
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        $stocks = BranchStock::where('branch_id', $employee->branch_id)
            ->whereIn('product_code', $products->pluck('code'))
            ->pluck('stock_actual', 'product_code');

        return view('employee.pages.initial-inventory.index', [
            'products'     => $products,
            'stocks'       => $stocks,
            'categories'   => Category::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get(['id', 'name']),
            'laboratories' => Laboratory::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Guarda el conteo de la página: solo las filas con cantidad o con costo/precio cambiados.
     */
    public function store(InitialInventoryRequest $request, InitialInventoryService $service)
    {
        $rows = [];
        foreach ($request->validated('rows') as $code => $row) {
            $rows[] = ['code' => (string) $code] + $row;
        }

        return $this->finish($service->apply(auth()->guard('employee')->user(), $rows));
    }

    /**
     * Plantilla de Excel (CSV) con los productos del filtro actual, para contar fuera del sistema.
     */
    public function template(Request $request)
    {
        $employee = auth()->guard('employee')->user();
        $stocks   = BranchStock::where('branch_id', $employee->branch_id)->pluck('stock_actual', 'product_code');

        $rows = $this->filtered($request, $employee)
            ->with('laboratory:id,name')
            ->orderBy('name')
            ->lazy()
            ->map(fn (Product $p) => [
                $p->code, $p->name, $p->laboratory?->name, (float) ($stocks[$p->code] ?? 0),
                '', (float) $p->purchase_price, (float) $p->unit_sale_price, '', '',
            ]);

        return CsvExport::download('inventario_inicial_' . now()->format('Ymd'), self::TEMPLATE, $rows);
    }

    /**
     * Importa la plantilla llena (Excel .xlsx o .csv). Las filas sin cantidad, costo ni precio se ignoran.
     */
    public function import(InitialInventoryImportRequest $request, InitialInventoryService $service)
    {
        $file = $request->file('file');

        try {
            $lines = strtolower($file->getClientOriginalExtension()) === 'xlsx'
                ? iterator_to_array(XlsxReader::rows($file->getRealPath()), false)
                : $this->csvLines($file->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo leer el archivo: ' . $e->getMessage());
        }

        [$rows, $errors] = $this->parse($lines);

        if ($rows === null) {
            return back()->with('error', 'El archivo no tiene las columnas de la plantilla (al menos "codigo" y "cantidad_contada"). Descarga la plantilla y llénala.');
        }

        $result = $service->apply(auth()->guard('employee')->user(), $rows);
        $result['errors'] = array_merge($errors, $result['errors']);

        return $this->finish($result);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** Productos activos de la compañía según los filtros de la pantalla */
    private function filtered(Request $request, $employee): Builder
    {
        return Product::query()
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->search($request->buscar, ['name', 'code', 'barcode', 'active_ingredient'])
            ->filterBy(['categoria' => 'category_id', 'laboratorio' => 'laboratory_id'])
            // "Solo sin stock": lo que aún no se contó en esta sede
            ->when($request->boolean('sin_stock'), fn ($q) => $q->whereNotIn('code', BranchStock::where('branch_id', $employee->branch_id)
                ->where('stock_actual', '>', 0)->select('product_code')));
    }

    /** Mensaje final: cuántos se contaron y, si hubo, qué filas fallaron */
    private function finish(array $result)
    {
        $summary = "Inventario guardado: {$result['counted']} producto(s) contado(s)"
            . ($result['prices'] ? ", {$result['prices']} precio(s) actualizado(s)" : '') . '.';

        if ($result['errors']) {
            return back()->with('error', $summary . ' Algunas filas no se guardaron:')->withErrors(array_slice($result['errors'], 0, 30));
        }

        if (!$result['counted'] && !$result['prices']) {
            return back()->with('error', 'No había nada que guardar: llena la cantidad contada (o cambia costo/precio) de algún producto.');
        }

        return back()->with('success', $summary);
    }

    /** Filas de un CSV (detecta ; o , como separador y quita el BOM de Excel) */
    private function csvLines(string $path): array
    {
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $first   = strtok($content, "\n");
        $sep     = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

        $lines = [];
        foreach (preg_split('/\r\n|\n|\r/', $content) as $line) {
            if (trim($line) !== '') {
                $lines[] = str_getcsv($line, $sep);
            }
        }

        return $lines;
    }

    /**
     * Convierte las filas leídas en filas para el servicio. Las columnas se ubican por su nombre.
     * Devuelve [filas, errores] o [null, []] si no hay encabezado de la plantilla.
     */
    private function parse(array $lines): array
    {
        $header = null;
        $rows   = [];
        $errors = [];

        foreach ($lines as $i => $line) {
            $line = array_map(fn ($v) => trim((string) $v), $line);

            if ($header === null) {
                $names = array_map(fn ($v) => Str::slug($v, '_'), $line);
                if (in_array('codigo', $names, true) && in_array('cantidad_contada', $names, true)) {
                    $header = array_flip($names);
                }
                continue;
            }

            $get  = fn (string $col) => $line[$header[$col] ?? -1] ?? '';
            $code = $get('codigo');
            if ($code === '') {
                continue;
            }

            $row = ['code' => $code, 'line' => $i + 1];
            foreach (['quantity' => 'cantidad_contada', 'cost' => 'costo_unitario', 'price' => 'precio_venta'] as $key => $col) {
                $value = str_replace(',', '.', str_replace(' ', '', $get($col)));
                if ($value === '') {
                    continue;
                }
                if (!is_numeric($value) || (float) $value < 0) {
                    $errors[] = 'Fila ' . ($i + 1) . ": «{$get($col)}» no es un número válido en {$col}.";
                    continue 2;
                }
                $row[$key] = (float) $value;
            }

            $row['batch'] = $get('lote') ?: null;
            if ($get('vencimiento') !== '') {
                $date = $this->date($get('vencimiento'));
                if (!$date) {
                    $errors[] = 'Fila ' . ($i + 1) . ": «{$get('vencimiento')}» no es una fecha válida (usa dd/mm/aaaa).";
                    continue;
                }
                $row['expiration'] = $date;
            }

            // Sin cantidad, costo ni precio no hay nada que hacer con la fila
            if (isset($row['quantity']) || isset($row['cost']) || isset($row['price'])) {
                $rows[] = $row;
            }
        }

        return [$header === null ? null : $rows, $errors];
    }

    /** Fecha del Excel: número de serie, dd/mm/aaaa o aaaa-mm-dd → Y-m-d */
    private function date(string $value): ?string
    {
        if ($serial = XlsxReader::serialDate($value)) {
            return $serial;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }
}

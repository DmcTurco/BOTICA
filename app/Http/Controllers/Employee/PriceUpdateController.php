<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Laboratory;
use App\Models\PriceChange;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceUpdateController extends Controller
{
    /**
     * Productos filtrables con sus precios editables y los últimos cambios registrados.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $products = $this->filtered($request, $employee->company_id)
            ->orderBy('name')->paginate(40)->withQueryString();

        return view('employee.pages.prices.index', [
            'products'     => $products,
            'categories'   => Category::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get(['id', 'name']),
            'laboratories' => Laboratory::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get(['id', 'name']),
            'changes'      => PriceChange::with(['product:code,name', 'employee:id,name'])
                ->where('company_id', $employee->company_id)->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    /**
     * Guarda los precios editados fila por fila. Solo se escriben los que cambiaron.
     */
    public function update(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $request->validate([
            'prices'                    => 'required|array',
            'prices.*.purchase_price'   => 'required|numeric|min:0|max:99999999',
            'prices.*.unit_sale_price'  => 'required|numeric|min:0|max:99999999',
        ], [
            'prices.*.purchase_price.required'  => 'Falta un precio de compra.',
            'prices.*.unit_sale_price.required' => 'Falta un precio de venta.',
            'prices.*.*.numeric'                => 'Los precios deben ser números.',
            'prices.*.*.min'                    => 'Los precios no pueden ser negativos.',
        ]);

        $products = Product::where('company_id', $employee->company_id)->whereIn('code', array_keys($request->prices))->get()->keyBy('code');

        $changed = 0;

        DB::transaction(function () use ($request, $products, $employee, &$changed) {
            foreach ($request->prices as $code => $values) {
                $product = $products->get($code);

                if ($product) {
                    $changed += $this->applyPrices($product, $values, $employee->id);
                }
            }
        });

        if ($changed) {
            \App\Models\AuditLog::record('price.update', 'Cambió ' . $changed . ' precio(s) de productos', null, ['cambios' => $changed]);
        }

        return back()->with('success', $changed ? "Se actualizaron {$changed} precio(s)." : 'No hubo cambios de precio.');
    }

    /**
     * Sube o baja un porcentaje a todos los productos que cumplen el filtro actual.
     */
    public function bulk(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $data = $request->validate([
            'percent' => 'required|numeric|min:-90|max:500|not_in:0',
            'target'  => 'required|in:sale,purchase,both',
        ], [
            'percent.not_in' => 'El porcentaje no puede ser 0.',
            'percent.min'    => 'No se puede bajar más de 90%.',
        ]);

        $factor  = 1 + ((float) $data['percent'] / 100);
        $changed = 0;

        DB::transaction(function () use ($request, $employee, $data, $factor, &$changed) {
            $this->filtered($request, $employee->company_id)->orderBy('code')->lockForUpdate()->get()->each(function (Product $product) use ($data, $factor, $employee, &$changed) {
                $new = [
                    'purchase_price'  => in_array($data['target'], ['purchase', 'both'], true) ? round((float) $product->purchase_price * $factor, 2) : (float) $product->purchase_price,
                    'unit_sale_price' => in_array($data['target'], ['sale', 'both'], true) ? round((float) $product->unit_sale_price * $factor, 2) : (float) $product->unit_sale_price,
                ];
                $changed += $this->applyPrices($product, $new, $employee->id);
            });
        });

        \App\Models\AuditLog::record('price.bulk', 'Aplicó ' . $data['percent'] . '% al ' . ['sale' => 'precio de venta', 'purchase' => 'precio de compra', 'both' => 'precio de compra y venta'][$data['target']] . ' de ' . $changed . ' precio(s)', null, ['filtro' => $request->query()]);

        return back()->with('success', "Se aplicó {$data['percent']}% y se actualizaron {$changed} precio(s).");
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** Productos activos de la compañía según los filtros de la pantalla */
    private function filtered(Request $request, int $companyId)
    {
        $query = Product::with(['category:id,name', 'laboratory:id,name'])
            ->where('company_id', $companyId)
            ->where('status', 1);

        if ($request->filled('buscar')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $request->buscar . '%')
                ->orWhere('code', 'like', '%' . $request->buscar . '%'));
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('laboratory')) {
            $query->where('laboratory_id', $request->laboratory);
        }

        return $query;
    }

    /**
     * Aplica los precios nuevos a un producto y deja cada cambio en la bitácora.
     * Devuelve cuántos precios cambiaron.
     */
    private function applyPrices(Product $product, array $values, int $employeeId): int
    {
        $count = 0;

        foreach (['purchase_price', 'unit_sale_price'] as $field) {
            $old = round((float) $product->{$field}, 2);
            $new = round((float) $values[$field], 2);

            if ($old === $new) {
                continue;
            }

            PriceChange::create([
                'company_id'   => $product->company_id,
                'product_code' => $product->code,
                'field'        => $field,
                'old_value'    => $old,
                'new_value'    => $new,
                'employee_id'  => $employeeId,
            ]);

            $product->{$field} = $new;
            $count++;
        }

        if ($count) {
            $product->save();
        }

        return $count;
    }
}

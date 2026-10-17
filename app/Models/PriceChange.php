<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceChange extends Model
{
    protected $table = 'price_changes';

    const FIELD_LABELS = [
        'purchase_price'  => 'Precio de compra',
        'unit_sale_price' => 'Precio de venta',
    ];

    protected $fillable = [
        'company_id',
        'product_code',
        'field',
        'old_value',
        'new_value',
        'employee_id',
    ];

    protected $casts = [
        'old_value' => 'decimal:2',
        'new_value' => 'decimal:2',
    ];

    /**
     * Aplica precios nuevos a un producto y deja cada cambio en la bitácora.
     * Solo toca los campos presentes en $values (purchase_price, unit_sale_price) que cambian.
     * Devuelve cuántos precios cambiaron.
     */
    public static function apply(Product $product, array $values, int $employeeId): int
    {
        $count = 0;

        foreach (array_keys(self::FIELD_LABELS) as $field) {
            if (!array_key_exists($field, $values) || $values[$field] === null || $values[$field] === '') {
                continue;
            }

            $old = round((float) $product->{$field}, 2);
            $new = round((float) $values[$field], 2);

            if ($old === $new) {
                continue;
            }

            self::create([
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

    /** Producto cuyo precio cambió */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'code')->withTrashed();
    }

    /** Empleado que hizo el cambio */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

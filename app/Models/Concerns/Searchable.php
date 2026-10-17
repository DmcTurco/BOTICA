<?php

namespace App\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Búsqueda y filtros centralizados para los listados.
 *
 * Uso en un controlador:
 *   Product::query()
 *       ->search($request->buscar, ['code', 'name', 'category.name', '=id'])
 *       ->filterBy(['categoria' => 'category_id', 'estado' => 'status']);
 */
trait Searchable
{
    /**
     * Busca un texto en varias columnas sin distinguir mayúsculas
     * (whereLike → ilike en PostgreSQL). Si el término está vacío no filtra nada.
     *
     * Formatos de columna:
     *   'name'          → columna propia
     *   'category.name' → columna de una relación (whereHas)
     *   '=id'           → comparación exacta, solo si el término es numérico
     *   Closure         → condición propia: fn (Builder $q, string $like) => ...
     *
     * @param  Builder  $query
     * @param  string|null  $term
     * @param  array<int, string|Closure>  $columns
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $term, array $columns): Builder
    {
        $term = trim((string) $term);

        if ($term === '' || $columns === []) {
            return $query;
        }

        // Escapa los comodines para que "%" o "_" escritos por el usuario se busquen literalmente
        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $query->where(function (Builder $q) use ($columns, $term, $like) {
            foreach ($columns as $column) {
                if ($column instanceof Closure) {
                    $q->orWhere(fn (Builder $w) => $column($w, $like));
                } elseif (str_starts_with($column, '=')) {
                    // Las columnas numéricas (ids) solo se comparan si el término es un número
                    if (ctype_digit($term)) {
                        $q->orWhere(substr($column, 1), (int) $term);
                    }
                } elseif (str_contains($column, '.')) {
                    $relation = substr($column, 0, strrpos($column, '.'));
                    $field    = substr($column, strrpos($column, '.') + 1);
                    $q->orWhereHas($relation, fn (Builder $r) => $r->whereLike($field, $like));
                } else {
                    $q->orWhereLike($column, $like);
                }
            }
        });
    }

    /**
     * Aplica filtros exactos desde la petición: solo los parámetros con valor.
     *
     * @param  Builder  $query
     * @param  array<string, string>  $filters  parámetro del request => columna
     * @return Builder
     */
    public function scopeFilterBy(Builder $query, array $filters): Builder
    {
        foreach ($filters as $param => $column) {
            if (request()->filled($param)) {
                $query->where($column, request()->input($param));
            }
        }

        return $query;
    }
}

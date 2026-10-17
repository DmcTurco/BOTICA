<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Recuerda la última URL de cada listado (con su búsqueda, filtros y página)
 * para que el botón "Volver" de un formulario regrese exactamente ahí.
 */
class ListUrl
{
    /**
     * Guarda la URL completa si la petición es la vista index de un recurso.
     *
     * @param  Request  $request
     * @return void
     */
    public static function remember(Request $request): void
    {
        $name = $request->route()?->getName();

        if ($request->isMethod('GET') && !$request->ajax() && $name && str_ends_with($name, '.index')) {
            session()->put('list_url.' . $name, $request->fullUrl());
        }
    }

    /**
     * URL a la que volver: la última visitada del listado, o el listado limpio.
     *
     * @param  string  $routeName  nombre de la ruta index, p. ej. 'employee.products.index'
     * @return string
     */
    public static function to(string $routeName): string
    {
        return session('list_url.' . $routeName) ?: route($routeName);
    }
}

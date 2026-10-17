<?php

namespace App\Http\Middleware;

use App\Support\ListUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarda la URL de los listados (index) para que "Volver" conserve filtros y página.
 */
class RememberListUrl
{
    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        ListUrl::remember($request);

        return $next($request);
    }
}

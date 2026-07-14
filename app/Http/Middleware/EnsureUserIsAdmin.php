<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasRole('Administrador')) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes permiso para acceder al panel administrativo.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasAnyPermission($permissions)) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }
}

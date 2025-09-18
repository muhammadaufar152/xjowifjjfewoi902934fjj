<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, ['officer', 'manager', 'avp', 'vp', 'admin'])) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}

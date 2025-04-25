<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

class JwtRole
{
    /**
     * @param  string  $requiredRole
     */
    public function handle(Request $request, Closure $next, $roles)
    {
        // explode on pipe to allow jwt.role:admin|alumni
        $allowed = explode('|', $roles);

        // you merged the claims in JwtMiddleware
        $claims = $request->get('auth_user', []);
        $role   = $claims['role'] ?? Session::get('user.role');

        if (! in_array($role, $allowed, true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}

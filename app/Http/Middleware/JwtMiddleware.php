<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        //$token = $request->bearerToken();
        $token = $request->bearerToken() ?? Session::get('api_token'); // ← fallback to session

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

            // Token expiration check
            if (isset($decoded->exp) && time() >= $decoded->exp) {
                return response()->json(['error' => 'Session expired, please log in again'], 401);
            }

            $request->merge(['auth_user' => (array) $decoded]);

            return $next($request);
        } catch (\Exception $e) {
            Session::forget(['api_token', 'user']);
            return redirect()->route('login')->withErrors('Invalid session, please log in again');
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;


class JwtAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $tokenString = session('api_token');
        if (! $tokenString) {
            return redirect()->route('login');
        }

        // Set up the symmetric signer + secret
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(config('services.auth_api.jwt_secret'))
        );

        try {
            // Parse
            $token = $config->parser()->parse($tokenString);

            // Ensure it's the correct token type
            if (! $token instanceof Plain) {
                throw new \RuntimeException('Invalid token type');
            }

            // Validate signature & expiration
            $validator = $config->validator();
            $validator->assert(
                $token,
                new SignedWith($config->signer(), $config->verificationKey()),
                new ValidAt(SystemClock::fromUTC())
            );
        } catch (\Throwable $e) {
            // Bad token → clear session & force login
            session()->forget(['api_token', 'user']);
            return redirect()->route('login');
        }

        // Now you can safely read claims()
        $claims = $token->claims();
        // e.g. $claims->get('sub'), $claims->get('role')

        // Share user info to all views
        view()->share('authUser', session('user', null));

        // Also attach claims if you need them later
        $request->attributes->set('jwt_claims', $claims);

        return $next($request);
    }
}

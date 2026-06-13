<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 双模认证：Bearer token 以 "ak_" 开头 → API Key 验证；否则 → JWT 验证。
 * 两者都通过 auth()->setUser() 注入用户，后续代码用 auth()->user() 透明取用。
 */
class ApiKeyOrJwtAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if ($bearer && str_starts_with($bearer, 'ak_')) {
            return $this->authenticateViaApiKey($request, $next, $bearer);
        }

        return $this->authenticateViaJwt($request, $next);
    }

    private function authenticateViaApiKey(Request $request, Closure $next, string $key): Response
    {
        $apiKey = ApiKey::where('key', $key)->with('user')->first();

        if (! $apiKey) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        $apiKey->updateQuietly(['last_used_at' => now()]);

        auth()->setUser($apiKey->user);

        return $next($request);
    }

    private function authenticateViaJwt(Request $request, Closure $next): Response
    {
        try {
            $user = auth('api')->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}

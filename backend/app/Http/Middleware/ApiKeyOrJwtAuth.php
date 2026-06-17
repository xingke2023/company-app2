<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\SsoAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 双模认证：Bearer token 以 "ak_" 开头 → API Key 验证；否则 → SSO JWT 验证。
 * 两者都通过 auth()->setUser() 注入用户，后续代码用 auth()->user() 透明取用。
 */
class ApiKeyOrJwtAuth
{
    public function __construct(private readonly SsoAuthService $sso) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if ($bearer && str_starts_with($bearer, 'ak_')) {
            return $this->authenticateViaApiKey($request, $next, $bearer);
        }

        return $this->authenticateViaSsoJwt($request, $next, $bearer);
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

    private function authenticateViaSsoJwt(Request $request, Closure $next, ?string $bearer): Response
    {
        if (! $bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $ssoUserId = $this->sso->decodeToken($bearer);
        } catch (\Throwable) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $this->sso->findOrCreateLocalUser($ssoUserId);

        auth()->setUser($user);
        auth('api')->setUser($user);

        return $next($request);
    }
}

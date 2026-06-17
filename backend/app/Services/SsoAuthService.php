<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * SSO 单点登录服务（认证中心 mo.xingke888.com）。
 *
 * 登录/刷新转发给认证中心；本地仅用共享密钥校验下发的 HS256 JWT，
 * 并把 SSO 用户映射到本地 users 行（sso_user_id），供 CRM/ERP 代码透明取用。
 */
class SsoAuthService
{
    private function baseUrl(): string
    {
        return rtrim((string) config('services.sso.base_url'), '/');
    }

    /**
     * 转发登录到认证中心。
     *
     * @return array{accessToken:string, refreshToken:string, user:array<string,mixed>}
     */
    public function login(string $identifier, string $password): array
    {
        $response = Http::acceptJson()->post($this->baseUrl().'/auth/login', [
            'identifier' => $identifier,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'identifier' => ['用户名或密码错误。'],
            ]);
        }

        return $response->json();
    }

    /**
     * 用 refreshToken 换取新的 token 对。
     *
     * @return array{accessToken:string, refreshToken:string}
     */
    public function refresh(string $refreshToken): array
    {
        $response = Http::acceptJson()->post($this->baseUrl().'/token/refresh', [
            'refreshToken' => $refreshToken,
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'refreshToken' => ['refreshToken 无效或已过期。'],
            ]);
        }

        return $response->json();
    }

    /**
     * 校验 SSO 下发的 HS256 JWT，返回 payload.sub（SSO 全局用户 ID）。
     *
     * @throws \Exception 解析或签名校验失败时抛出
     */
    public function decodeToken(string $jwt): string
    {
        $payload = JWT::decode($jwt, new Key((string) config('services.sso.jwt_secret'), 'HS256'));

        return (string) $payload->sub;
    }

    /**
     * 按 sso_user_id 查本地用户，不存在则创建；有 attrs 时同步资料。
     *
     * @param  array{username?:string, email?:string}  $attrs
     */
    public function findOrCreateLocalUser(string $ssoUserId, array $attrs = []): User
    {
        $user = User::query()->where('sso_user_id', $ssoUserId)->first();

        $name = $attrs['username'] ?? $user?->name ?? $ssoUserId;
        $email = $attrs['email'] ?? $user?->email ?? "{$ssoUserId}@sso.local";

        if (! $user) {
            return User::create([
                'sso_user_id' => $ssoUserId,
                'name' => $name,
                'email' => $email,
            ]);
        }

        if (! empty($attrs)) {
            $user->fill(['name' => $name, 'email' => $email])->save();
        }

        return $user;
    }
}

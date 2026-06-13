'use client';

import React, { createContext, useContext, useState, useEffect } from 'react';
import { authApi } from './api';
import type { User, LoginCredentials, RegisterCredentials } from './api';

// localStorage 在微信 web-view / 无痕模式可能不可用，包一层防止报错中断初始化流程
const safeLS = {
  get(k: string): string | null {
    try { return window.localStorage.getItem(k); } catch { return null; }
  },
  set(k: string, v: string): void {
    try { window.localStorage.setItem(k, v); } catch { /* ignore */ }
  },
  remove(k: string): void {
    try { window.localStorage.removeItem(k); } catch { /* ignore */ }
  },
};

interface AuthContextType {
  user: User | null;
  token: string | null;
  loading: boolean;
  login: (credentials: LoginCredentials) => Promise<void>;
  register: (credentials: RegisterCredentials) => Promise<void>;
  logout: () => Promise<void>;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // 读 URL 里的 ?token=（小程序 web-view 带入的 app2 JWT）或 localStorage 恢复
    let urlToken: string | null = null;
    try {
      urlToken = new URLSearchParams(window.location.search).get('token');
    } catch { urlToken = null; }

    if (urlToken) {
      // 立刻设置 token → setLoading(false)，不等 /me 响应（/me 是 fire-and-forget）
      // 原因：await /me 在微信 webview 网络慢时会无限阻塞，导致页面永远「加载中」
      setToken(urlToken);
      safeLS.set('auth_token', urlToken);

      // 后台拿 user 信息，拿到再填；不影响主流程
      authApi.me(urlToken)
        .then((u: User) => {
          setUser(u);
          safeLS.set('auth_user', JSON.stringify(u));
        })
        .catch(() => { /* token 过期/无效时 user 为 null，页面仍可渲染 */ });

      // 抹掉 URL 里的 token（不刷页），微信 webview 可能不支持，忽略报错
      try {
        const url = new URL(window.location.href);
        url.searchParams.delete('token');
        window.history.replaceState({}, '', url.pathname + url.search + url.hash);
      } catch { /* ignore */ }

      setLoading(false);
      return;
    }

    // 从 localStorage 恢复登录态
    const storedToken = safeLS.get('auth_token');
    const storedUser = safeLS.get('auth_user');
    if (storedToken && storedUser) {
      try {
        setToken(storedToken);
        setUser(JSON.parse(storedUser));
      } catch {
        safeLS.remove('auth_token');
        safeLS.remove('auth_user');
      }
    }
    setLoading(false);
  }, []);

  const login = async (credentials: LoginCredentials) => {
    const response = await authApi.login(credentials);
    setToken(response.access_token);
    setUser(response.user);
    safeLS.set('auth_token', response.access_token);
    safeLS.set('auth_user', JSON.stringify(response.user));
  };

  const register = async (credentials: RegisterCredentials) => {
    const response = await authApi.register(credentials);
    setToken(response.access_token);
    setUser(response.user);
    safeLS.set('auth_token', response.access_token);
    safeLS.set('auth_user', JSON.stringify(response.user));
  };

  const logout = async () => {
    if (token) {
      try { await authApi.logout(token); } catch { /* ignore */ }
    }
    setToken(null);
    setUser(null);
    safeLS.remove('auth_token');
    safeLS.remove('auth_user');
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        loading,
        login,
        register,
        logout,
        isAuthenticated: !!user && !!token,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

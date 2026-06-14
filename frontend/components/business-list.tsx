'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';

interface ListItem {
  id: number;
  primary: string;
  secondary: string;
  badge?: string;
}

interface BusinessListProps {
  title: string;
  fetchData: (token: string, search?: string) => Promise<{ data: unknown[] }>;
  transform: (item: unknown) => ListItem;
  searchPlaceholder?: string;
}

export function BusinessList({ title, fetchData, transform, searchPlaceholder }: BusinessListProps) {
  const { token, loading: authLoading } = useAuth();
  const router = useRouter();
  const [items, setItems] = useState<ListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');

  const load = async (currentToken: string, q?: string) => {
    setLoading(true);
    setError('');
    try {
      const res = await fetchData(currentToken, q);
      setItems((res.data as unknown[]).map(transform));
    } catch (err) {
      const msg = err instanceof Error ? err.message : '加载失败';
      if (msg.includes('401') || msg.toLowerCase().includes('unauthorized') || msg.toLowerCase().includes('unauthenticated')) {
        router.replace('/login');
      } else {
        setError(msg);
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (authLoading) return;
    if (!token) {
      router.replace('/login');
      return;
    }
    load(token); // 把 token 作参数传入，避免闭包捕获旧值
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, token]);

  if (authLoading) {
    return <div className="flex h-screen items-center justify-center text-sm text-gray-400">加载中…</div>;
  }

  return (
    <div className="min-h-screen bg-gray-50 pb-6">
      {/* 搜索栏 */}
      <div className="bg-white px-4 py-3 border-b">
        <form
          onSubmit={(e) => { e.preventDefault(); if (token) load(token, search.trim() || undefined); }}
          className="flex gap-2"
        >
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={searchPlaceholder || '搜索…'}
            className="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 placeholder-gray-400 outline-none focus:border-[#E65C46] focus:ring-1 focus:ring-[#E65C46]"
          />
          <button
            type="submit"
            className="rounded-lg bg-[#E65C46] px-4 py-2 text-sm font-medium text-white active:opacity-80"
          >
            搜索
          </button>
        </form>
      </div>

      {/* 内容区 */}
      <div className="px-4 pt-3">
        {/* 加载中 */}
        {loading && (
          <div className="flex flex-col gap-2">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-16 animate-pulse rounded-xl bg-white" />
            ))}
          </div>
        )}

        {/* 错误 */}
        {error && (
          <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{error}</div>
        )}

        {/* 空态 */}
        {!loading && !error && items.length === 0 && (
          <div className="flex flex-col items-center py-16 text-gray-400">
            <span className="mb-2 text-4xl">📭</span>
            <p className="text-sm">暂无数据</p>
          </div>
        )}

        {/* 列表 */}
        {!loading && !error && items.length > 0 && (
          <>
            <p className="mb-2 text-xs text-gray-400">共 {items.length} 条</p>
            <div className="flex flex-col gap-2">
              {items.map((item) => (
                <div
                  key={item.id}
                  className="flex items-center justify-between rounded-xl bg-white px-4 py-3 shadow-sm"
                >
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-gray-800">{item.primary}</p>
                    <p className="mt-0.5 truncate text-xs text-gray-500">{item.secondary}</p>
                  </div>
                  {item.badge && (
                    <span className="ml-3 shrink-0 rounded-full bg-[#E65C46]/10 px-2.5 py-0.5 text-xs font-medium text-[#E65C46]">
                      {item.badge}
                    </span>
                  )}
                </div>
              ))}
            </div>
          </>
        )}
      </div>
    </div>
  );
}

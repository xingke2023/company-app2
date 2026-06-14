'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';
import { salesOrdersApi } from '@/lib/api';
import type { SalesOrder } from '@/lib/api';

const STATUS_LABEL: Record<string, string> = {
  pending: '待确认',
  confirmed: '已确认',
  shipped: '已发货',
  completed: '已完成',
  cancelled: '已取消',
};

const STATUS_COLOR: Record<string, string> = {
  pending: 'text-yellow-600',
  confirmed: 'text-blue-600',
  shipped: 'text-indigo-600',
  completed: 'text-green-600',
  cancelled: 'text-gray-400',
};

export default function SalesOrdersPage() {
  const { token, loading: authLoading } = useAuth();
  const router = useRouter();
  const [items, setItems] = useState<SalesOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');

  const load = async (currentToken: string, q?: string) => {
    setLoading(true);
    setError('');
    try {
      const res = await salesOrdersApi.list(currentToken, q);
      setItems(res.data as SalesOrder[]);
    } catch (err) {
      const msg = err instanceof Error ? err.message : '加载失败';
      if (
        msg.includes('401') ||
        msg.toLowerCase().includes('unauthorized') ||
        msg.toLowerCase().includes('unauthenticated')
      ) {
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
    if (!token) { router.replace('/login'); return; }
    load(token);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, token]);

  if (authLoading) {
    return <div className="flex h-screen items-center justify-center text-xs text-gray-400">加载中…</div>;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* 搜索栏 */}
      <div className="border-b bg-white px-3 py-2">
        <form
          onSubmit={(e) => { e.preventDefault(); if (token) load(token, search.trim() || undefined); }}
          className="flex gap-2"
        >
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="搜索单号或客户名…"
            className="flex-1 rounded border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs text-gray-800 placeholder-gray-400 outline-none focus:border-[#E65C46]"
          />
          <button type="submit" className="rounded bg-[#E65C46] px-3 py-1.5 text-xs font-medium text-white active:opacity-80">
            搜索
          </button>
        </form>
      </div>

      <div className="px-3 pt-2 pb-4">
        {loading && (
          <div className="flex flex-col gap-1">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="h-8 animate-pulse rounded bg-gray-200" />
            ))}
          </div>
        )}

        {error && (
          <div className="rounded bg-red-50 px-3 py-2 text-xs text-red-600">{error}</div>
        )}

        {!loading && !error && items.length === 0 && (
          <div className="flex flex-col items-center py-12 text-gray-400">
            <span className="mb-1 text-3xl">📭</span>
            <p className="text-xs">暂无订单</p>
          </div>
        )}

        {!loading && !error && items.length > 0 && (
          <>
            <p className="mb-1 text-xs text-gray-400">{items.length} 条</p>
            <div className="divide-y divide-gray-100 bg-white">
              {items.map((o) => (
                <div key={o.id} className="flex items-center justify-between px-3 py-2">
                  <div className="min-w-0">
                    <span className="text-xs font-medium text-gray-800">{o.order_no}</span>
                    {o.customer?.name && (
                      <span className="ml-2 text-xs text-gray-500">{o.customer.name}</span>
                    )}
                    {o.ordered_at && (
                      <span className="ml-2 text-[10px] text-gray-400">
                        {o.ordered_at.slice(0, 10)}
                      </span>
                    )}
                  </div>
                  <div className="ml-3 flex shrink-0 items-center gap-2">
                    <span className="text-xs font-medium text-gray-700">¥{o.total_amount}</span>
                    <span className={`text-[10px] font-medium ${STATUS_COLOR[o.status] ?? 'text-gray-500'}`}>
                      {STATUS_LABEL[o.status] ?? o.status}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </>
        )}
      </div>
    </div>
  );
}

'use client';

import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';
import { productsApi } from '@/lib/api';
import type { Product } from '@/lib/api';

export default function ProductsPage() {
  const { token, loading: authLoading } = useAuth();
  const router = useRouter();
  const [items, setItems] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [activeCat, setActiveCat] = useState<number | null>(null);

  const load = async (currentToken: string, q?: string) => {
    setLoading(true);
    setError('');
    try {
      const res = await productsApi.list(currentToken, q);
      setItems(res.data as Product[]);
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

  // 从商品列表提取分类（保持首次出现顺序）
  const categories = useMemo(() => {
    const seen = new Map<number, string>();
    for (const p of items) {
      if (p.category && !seen.has(p.category.id)) seen.set(p.category.id, p.category.name);
    }
    return Array.from(seen.entries()).map(([id, name]) => ({ id, name }));
  }, [items]);

  const filtered = useMemo(
    () => (activeCat === null ? items : items.filter((p) => p.category?.id === activeCat)),
    [items, activeCat],
  );

  if (authLoading) {
    return <div className="flex h-screen items-center justify-center text-sm text-gray-400">加载中…</div>;
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
            placeholder="搜索商品名或SKU…"
            className="flex-1 rounded border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs text-gray-800 placeholder-gray-400 outline-none focus:border-[#E65C46]"
          />
          <button type="submit" className="rounded bg-[#E65C46] px-3 py-1.5 text-xs font-medium text-white active:opacity-80">
            搜索
          </button>
        </form>
      </div>

      {/* 分类标签 — 始终显示在搜索栏下方 */}
      <div className="flex gap-1.5 overflow-x-auto border-b bg-white px-3 py-2 scrollbar-none">
        <button
          onClick={() => setActiveCat(null)}
          className={`shrink-0 rounded-full px-3 py-0.5 text-xs font-medium ${
            activeCat === null ? 'bg-[#E65C46] text-white' : 'bg-gray-100 text-gray-600'
          }`}
        >
          全部
        </button>
        {categories.map((c) => (
          <button
            key={c.id}
            onClick={() => setActiveCat(activeCat === c.id ? null : c.id)}
            className={`shrink-0 rounded-full px-3 py-0.5 text-xs font-medium ${
              activeCat === c.id ? 'bg-[#E65C46] text-white' : 'bg-gray-100 text-gray-600'
            }`}
          >
            {c.name}
          </button>
        ))}
      </div>

      {/* 内容区 */}
      <div className="px-3 pt-2 pb-4">
        {loading && (
          <div className="grid grid-cols-2 gap-px">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="h-14 animate-pulse bg-gray-200" />
            ))}
          </div>
        )}

        {error && (
          <div className="rounded bg-red-50 px-3 py-2 text-xs text-red-600">{error}</div>
        )}

        {!loading && !error && filtered.length === 0 && (
          <div className="flex flex-col items-center py-12 text-gray-400">
            <span className="mb-1 text-3xl">📭</span>
            <p className="text-xs">暂无商品</p>
          </div>
        )}

        {!loading && !error && filtered.length > 0 && (
          <>
            <p className="mb-1.5 text-xs text-gray-400">{filtered.length} 件</p>
            {/* 两列网格，无卡片，用细线分隔 */}
            <div className="grid grid-cols-2 divide-x divide-y divide-gray-100 border border-gray-100 bg-white">
              {filtered.map((p) => {
                const isLow = p.min_stock !== null && p.stock_quantity <= (p.min_stock ?? 0);
                return (
                  <div key={p.id} className="px-2.5 py-2">
                    <div className="flex items-start justify-between gap-1">
                      <span className="text-xs font-medium text-gray-800 leading-tight line-clamp-1">{p.name}</span>
                      <span className={`shrink-0 text-[10px] ${p.status === 'active' ? 'text-green-600' : 'text-gray-400'}`}>
                        {p.status === 'active' ? '在售' : '下架'}
                      </span>
                    </div>
                    <p className="mt-0.5 text-[10px] text-gray-400 leading-tight">{p.sku}</p>
                    <div className="mt-1 flex items-center justify-between">
                      <span className="text-xs font-semibold text-[#E65C46]">¥{p.sell_price}</span>
                      <span className={`text-[10px] ${isLow ? 'text-red-500' : 'text-gray-400'}`}>
                        {p.stock_quantity}{p.unit}{isLow ? ' ⚠️' : ''}
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>
          </>
        )}
      </div>
    </div>
  );
}

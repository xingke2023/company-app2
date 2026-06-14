'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';
import { customersApi } from '@/lib/api';
import type { Customer } from '@/lib/api';

const STATUS_TABS = [
  { value: '', label: '全部' },
  { value: 'active', label: '活跃' },
  { value: 'potential', label: '潜在' },
  { value: 'inactive', label: '非活跃' },
];

const TYPE_LABEL: Record<string, string> = {
  individual: '个人',
  company: '企业',
};

export default function CustomersPage() {
  const { token, loading: authLoading } = useAuth();
  const router = useRouter();
  const [items, setItems] = useState<Customer[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [activeStatus, setActiveStatus] = useState('');

  const load = async (currentToken: string, q?: string, status?: string) => {
    setLoading(true);
    setError('');
    try {
      const res = await customersApi.list(currentToken, q, status);
      setItems(res.data as Customer[]);
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
    load(token, undefined, undefined);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, token]);

  const handleStatusChange = (status: string) => {
    setActiveStatus(status);
    if (token) load(token, search.trim() || undefined, status || undefined);
  };

  if (authLoading) {
    return <div className="flex h-screen items-center justify-center text-xs text-gray-400">加载中…</div>;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* 搜索栏 */}
      <div className="border-b bg-white px-3 py-2">
        <form
          onSubmit={(e) => {
            e.preventDefault();
            if (token) load(token, search.trim() || undefined, activeStatus || undefined);
          }}
          className="flex gap-2"
        >
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="搜索姓名/公司/电话…"
            className="flex-1 rounded border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs text-gray-800 placeholder-gray-400 outline-none focus:border-[#E65C46]"
          />
          <button type="submit" className="rounded bg-[#E65C46] px-3 py-1.5 text-xs font-medium text-white active:opacity-80">
            搜索
          </button>
        </form>
      </div>

      {/* 状态标签 */}
      <div className="flex gap-1.5 overflow-x-auto border-b bg-white px-3 py-2 scrollbar-none">
        {STATUS_TABS.map((tab) => (
          <button
            key={tab.value}
            onClick={() => handleStatusChange(tab.value)}
            className={`shrink-0 rounded-full px-3 py-0.5 text-xs font-medium ${
              activeStatus === tab.value ? 'bg-[#E65C46] text-white' : 'bg-gray-100 text-gray-600'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* 列表 */}
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
            <p className="text-xs">暂无客户</p>
          </div>
        )}

        {!loading && !error && items.length > 0 && (
          <>
            <p className="mb-1 text-xs text-gray-400">{items.length} 位</p>
            <div className="divide-y divide-gray-100 bg-white">
              {items.map((c) => (
                <div key={c.id} className="flex cursor-pointer items-center justify-between px-3 py-2 active:bg-gray-50" onClick={() => router.push(`/customers/${c.id}`)}>
                  <div className="min-w-0">
                    <span className="text-xs font-medium text-gray-800">{c.name}</span>
                    {c.company && (
                      <span className="ml-2 text-xs text-gray-500">{c.company}</span>
                    )}
                    {c.phone && (
                      <span className="ml-2 text-[10px] text-gray-400">{c.phone}</span>
                    )}
                  </div>
                  <div className="ml-3 flex shrink-0 items-center gap-2">
                    {c.type && (
                      <span className="text-[10px] text-gray-400">{TYPE_LABEL[c.type] ?? c.type}</span>
                    )}
                    <span className={`text-[10px] font-medium ${
                      c.status === 'active' ? 'text-green-600' :
                      c.status === 'potential' ? 'text-blue-500' : 'text-gray-400'
                    }`}>
                      {c.status === 'active' ? '活跃' : c.status === 'potential' ? '潜在' : '非活跃'}
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

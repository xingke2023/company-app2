'use client';

import { useEffect, useRef, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';
import { customersApi, activitiesApi } from '@/lib/api';
import type { CustomerDetail, Activity } from '@/lib/api';

const TYPE_LABEL: Record<string, string> = { call: '电话', meeting: '会议', email: '邮件', visit: '拜访', other: '其他' };
const STATUS_LABEL: Record<string, string> = { active: '活跃', inactive: '非活跃', potential: '潜在' };
const STATUS_COLOR: Record<string, string> = { active: 'text-green-600', potential: 'text-blue-500', inactive: 'text-gray-400' };

function fmt(dt: string | null) {
  if (!dt) return '';
  return dt.slice(0, 16).replace('T', ' ');
}

interface ChatMsg { role: 'user' | 'ai'; text: string }

export default function CustomerDetailPage() {
  const { token, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const id = Number(params.id);

  const [customer, setCustomer] = useState<CustomerDetail | null>(null);
  const [activities, setActivities] = useState<Activity[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // 聊天
  const [msgs, setMsgs] = useState<ChatMsg[]>([]);
  const [input, setInput] = useState('');
  const [sending, setSending] = useState(false);
  const chatEndRef = useRef<HTMLDivElement>(null);

  const loadActivities = async (t: string) => {
    const aRes = await activitiesApi.listByCustomer(t, id);
    setActivities((aRes as { data: Activity[] }).data ?? []);
  };

  useEffect(() => {
    if (authLoading) return;
    if (!token) { router.replace('/login'); return; }
    const load = async () => {
      setLoading(true);
      try {
        const [cRes, aRes] = await Promise.all([
          customersApi.get(token, id),
          activitiesApi.listByCustomer(token, id),
        ]);
        setCustomer(cRes as CustomerDetail);
        setActivities((aRes as { data: Activity[] }).data ?? []);
      } catch (err) {
        const msg = err instanceof Error ? err.message : '加载失败';
        if (msg.includes('401') || msg.toLowerCase().includes('unauthorized')) {
          router.replace('/login');
        } else {
          setError(msg);
        }
      } finally {
        setLoading(false);
      }
    };
    load();
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, token, id]);

  useEffect(() => {
    chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [msgs]);

  const send = async () => {
    const text = input.trim();
    if (!text || sending) return;
    setInput('');
    setMsgs((prev) => [...prev, { role: 'user', text }]);
    setSending(true);
    try {
      // 带上客户姓名让 AI 能关联到正确客户
      const contextText = customer ? `（客户：${customer.name}）${text}` : text;
      const res = await fetch('/api/ai/message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text: contextText }),
      });
      const data = await res.json();
      setMsgs((prev) => [...prev, { role: 'ai', text: data.reply ?? '已处理' }]);
      // 如果是跟进类意图，刷新记录列表
      if (token && data.intent?.includes('activity')) {
        await loadActivities(token);
      }
    } catch {
      setMsgs((prev) => [...prev, { role: 'ai', text: '发送失败，请重试' }]);
    } finally {
      setSending(false);
    }
  };

  if (authLoading || loading) {
    return <div className="flex h-screen items-center justify-center text-xs text-gray-400">加载中…</div>;
  }
  if (error) return <div className="p-4 text-xs text-red-500">{error}</div>;
  if (!customer) return null;

  return (
    <div className="flex min-h-screen flex-col bg-gray-50">
      {/* 顶栏 */}
      <div className="flex items-center gap-2 border-b bg-white px-3 py-2.5">
        <button onClick={() => router.back()} className="text-[#E65C46] text-xs">← 返回</button>
        <span className="text-sm font-semibold text-gray-800">{customer.name}</span>
      </div>

      {/* 滚动内容区 */}
      <div className="flex-1 overflow-y-auto pb-36">

        {/* 基本信息 */}
        <div className="mx-3 mt-3 divide-y divide-gray-100 rounded bg-white text-xs">
          {customer.company && (
            <div className="flex justify-between px-3 py-2">
              <span className="text-gray-400">公司</span>
              <span className="text-gray-800">{customer.company}</span>
            </div>
          )}
          {customer.phone && (
            <div className="flex justify-between px-3 py-2">
              <span className="text-gray-400">电话</span>
              <a href={`tel:${customer.phone}`} className="text-[#E65C46]">{customer.phone}</a>
            </div>
          )}
          {customer.email && (
            <div className="flex justify-between px-3 py-2">
              <span className="text-gray-400">邮箱</span>
              <span className="text-gray-700">{customer.email}</span>
            </div>
          )}
          {customer.address && (
            <div className="flex justify-between px-3 py-2">
              <span className="text-gray-400">地址</span>
              <span className="text-gray-700">{customer.address}</span>
            </div>
          )}
          <div className="flex justify-between px-3 py-2">
            <span className="text-gray-400">状态</span>
            <span className={`font-medium ${STATUS_COLOR[customer.status] ?? 'text-gray-500'}`}>
              {STATUS_LABEL[customer.status] ?? customer.status}
            </span>
          </div>
          {customer.notes && (
            <div className="px-3 py-2">
              <span className="text-gray-400">备注</span>
              <p className="mt-1 text-gray-700 leading-relaxed">{customer.notes}</p>
            </div>
          )}
        </div>

        {/* 跟进记录 */}
        <div className="mx-3 mt-4">
          <p className="mb-2 text-xs font-medium text-gray-500">
            跟进记录{activities.length > 0 && <span className="ml-1 text-gray-400">（{activities.length} 条）</span>}
          </p>
          {activities.length === 0 ? (
            <p className="text-xs text-gray-400">暂无跟进记录，在下方输入框发消息即可添加</p>
          ) : (
            <div className="relative">
              <div className="absolute left-[5px] top-2 bottom-2 w-px bg-gray-200" />
              <div className="flex flex-col gap-3 pl-5">
                {activities.map((a) => (
                  <div key={a.id} className="relative">
                    <div className={`absolute -left-5 top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white ${a.completed_at ? 'bg-green-500' : 'bg-gray-300'}`} />
                    <div className="rounded bg-white px-3 py-2 text-xs">
                      <div className="flex items-center justify-between">
                        <span className="font-medium text-gray-800">
                          {TYPE_LABEL[a.type] ?? a.type}
                          {a.title ? `：${a.title}` : ''}
                        </span>
                        <span className={`text-[10px] ${a.completed_at ? 'text-green-600' : 'text-gray-400'}`}>
                          {a.completed_at ? '已完成' : '未完成'}
                        </span>
                      </div>
                      <p className="mt-0.5 text-[10px] text-gray-400">{fmt(a.scheduled_at ?? a.created_at)}</p>
                      {a.description && (
                        <p className="mt-1.5 rounded bg-gray-50 px-2 py-1.5 text-gray-700 leading-relaxed">
                          {a.description}
                        </p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* AI 对话气泡 */}
        {msgs.length > 0 && (
          <div className="mx-3 mt-4 flex flex-col gap-2">
            {msgs.map((m, i) => (
              <div key={i} className={`flex ${m.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                <div className={`max-w-[80%] rounded-2xl px-3 py-2 text-xs leading-relaxed ${
                  m.role === 'user'
                    ? 'bg-[#E65C46] text-white'
                    : 'bg-white text-gray-700 shadow-sm'
                }`}>
                  {m.text}
                </div>
              </div>
            ))}
            {sending && (
              <div className="flex justify-start">
                <div className="rounded-2xl bg-white px-3 py-2 text-xs text-gray-400 shadow-sm">正在处理…</div>
              </div>
            )}
            <div ref={chatEndRef} />
          </div>
        )}
      </div>

      {/* 底部输入框 — 固定 */}
      <div className="fixed bottom-0 left-0 right-0 border-t bg-white px-3 py-2 shadow-lg">
        <div className="mb-1 text-[10px] text-gray-400">
          例：刚给{customer.name}打了电话，谈了报价 · 安排了下周三会议
        </div>
        <form onSubmit={(e) => { e.preventDefault(); send(); }} className="flex gap-2">
          <input
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder={`记录与${customer.name}的沟通…`}
            className="flex-1 rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-800 outline-none focus:border-[#E65C46]"
          />
          <button
            type="submit"
            disabled={sending || !input.trim()}
            className="rounded-full bg-[#E65C46] px-4 py-1.5 text-xs font-medium text-white disabled:opacity-40"
          >
            发送
          </button>
        </form>
      </div>
    </div>
  );
}

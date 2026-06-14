'use client';

import { useEffect, useRef, useState } from 'react';
import { assistantApi } from '@/lib/api';
import type { AssistantNote } from '@/lib/api';

type TabType = 'note' | 'plan' | 'ai_suggestion';

const TABS: { value: TabType; label: string; emoji: string }[] = [
  { value: 'note', label: '便签', emoji: '📌' },
  { value: 'plan', label: '计划', emoji: '✅' },
  { value: 'ai_suggestion', label: 'AI建议', emoji: '💡' },
];

function formatDate(iso: string): string {
  const d = new Date(iso);
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(today.getDate() - 1);
  const ds = d.toDateString();
  if (ds === today.toDateString()) return '今天';
  if (ds === yesterday.toDateString()) return '昨天';
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function formatTime(iso: string): string {
  const d = new Date(iso);
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

function groupByDate(notes: AssistantNote[]): { date: string; items: AssistantNote[] }[] {
  const map = new Map<string, AssistantNote[]>();
  for (const n of notes) {
    const key = formatDate(n.created_at);
    if (!map.has(key)) map.set(key, []);
    map.get(key)!.push(n);
  }
  return Array.from(map.entries()).map(([date, items]) => ({ date, items }));
}

export default function AssistantPage() {
  const [activeTab, setActiveTab] = useState<TabType>('note');
  const [notes, setNotes] = useState<AssistantNote[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [content, setContent] = useState('');
  const [inputType, setInputType] = useState<'note' | 'plan'>('note');
  const [submitting, setSubmitting] = useState(false);
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  const load = async (type: TabType) => {
    setLoading(true);
    setError('');
    try {
      const data = await assistantApi.list(type);
      setNotes(data);
    } catch {
      setError('加载失败，请重试');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load(activeTab);
    if (activeTab !== 'ai_suggestion') {
      setInputType(activeTab as 'note' | 'plan');
    }
  }, [activeTab]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const text = content.trim();
    if (!text) return;
    setSubmitting(true);
    try {
      await assistantApi.create(inputType, text);
      setContent('');
      if (activeTab === inputType) {
        await load(activeTab);
      }
    } catch {
      setError('保存失败，请重试');
    } finally {
      setSubmitting(false);
      textareaRef.current?.focus();
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await assistantApi.delete(id);
      setNotes((prev) => prev.filter((n) => n.id !== id));
    } catch {
      setError('删除失败');
    }
  };

  const groups = groupByDate(notes);
  const activeTabDef = TABS.find((t) => t.value === activeTab)!;

  return (
    <div className="flex min-h-screen flex-col bg-gray-50">
      {/* 标签栏 */}
      <div className="sticky top-0 z-10 flex border-b bg-white">
        {TABS.map((tab) => (
          <button
            key={tab.value}
            onClick={() => setActiveTab(tab.value)}
            className={`flex-1 py-2.5 text-xs font-medium transition-colors ${
              activeTab === tab.value
                ? 'border-b-2 border-[#E65C46] text-[#E65C46]'
                : 'text-gray-500'
            }`}
          >
            {tab.emoji} {tab.label}
          </button>
        ))}
      </div>

      {/* 内容区 */}
      <div className={`flex-1 overflow-y-auto px-3 py-3 ${activeTab !== 'ai_suggestion' ? 'pb-24' : ''}`}>
        {loading && (
          <div className="flex flex-col gap-2">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-12 animate-pulse rounded-lg bg-white" />
            ))}
          </div>
        )}

        {error && (
          <div className="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600">{error}</div>
        )}

        {!loading && !error && notes.length === 0 && (
          <div className="flex flex-col items-center py-16 text-gray-400">
            <span className="mb-1 text-4xl">{activeTabDef.emoji}</span>
            <p className="text-xs">
              {activeTab === 'ai_suggestion' ? '在聊天中向AI提问，建议会自动保存到这里' : `还没有${activeTabDef.label}，快记录一条吧`}
            </p>
          </div>
        )}

        {!loading && !error && groups.length > 0 && (
          <div className="flex flex-col gap-4">
            {groups.map(({ date, items }) => (
              <div key={date}>
                <p className="mb-1.5 text-[10px] font-medium text-gray-400">{date}</p>
                <div className="flex flex-col gap-1.5">
                  {items.map((note) => (
                    <div
                      key={note.id}
                      className="relative rounded-lg bg-white px-3 py-2.5 shadow-sm"
                    >
                      <button
                        onClick={() => handleDelete(note.id)}
                        className="absolute right-2 top-2 text-gray-300 hover:text-red-400"
                        aria-label="删除"
                      >
                        ×
                      </button>
                      <p className="pr-4 text-xs leading-relaxed whitespace-pre-wrap text-gray-800">
                        {note.content}
                      </p>
                      <p className="mt-1 text-[10px] text-gray-400">{formatTime(note.created_at)}</p>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        )}

        {/* AI建议 tab 底部说明 */}
        {activeTab === 'ai_suggestion' && notes.length > 0 && (
          <p className="mt-4 text-center text-[10px] text-gray-400">AI建议由聊天自动保存</p>
        )}
      </div>

      {/* 底部输入栏（AI建议 tab 隐藏） */}
      {activeTab !== 'ai_suggestion' && (
        <div className="fixed inset-x-0 bottom-0 border-t bg-white px-3 py-2.5 shadow-lg">
          <form onSubmit={handleSubmit} className="flex items-end gap-2">
            <textarea
              ref={textareaRef}
              value={content}
              onChange={(e) => setContent(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                  e.preventDefault();
                  handleSubmit(e as unknown as React.FormEvent);
                }
              }}
              rows={2}
              placeholder={activeTab === 'note' ? '记录一条便签…' : '添加一个计划…'}
              className="flex-1 resize-none rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs text-gray-800 placeholder-gray-400 outline-none focus:border-[#E65C46]"
            />
            <div className="flex flex-col items-end gap-1.5">
              {/* 类型切换 */}
              <select
                value={inputType}
                onChange={(e) => setInputType(e.target.value as 'note' | 'plan')}
                className="rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] text-gray-600 outline-none"
              >
                <option value="note">📌 便签</option>
                <option value="plan">✅ 计划</option>
              </select>
              <button
                type="submit"
                disabled={submitting || !content.trim()}
                className="rounded-lg bg-[#E65C46] px-3 py-1.5 text-xs font-medium text-white disabled:opacity-40 active:opacity-80"
              >
                {submitting ? '…' : '记录'}
              </button>
            </div>
          </form>
        </div>
      )}
    </div>
  );
}

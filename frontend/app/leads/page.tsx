'use client';

import { BusinessList } from '@/components/business-list';
import { leadsApi } from '@/lib/api';
import type { Lead } from '@/lib/api';

export default function LeadsPage() {
  return (
    <BusinessList
      title="线索"
      searchPlaceholder="搜索姓名或公司…"
      fetchData={(token, search) => leadsApi.list(token, search)}
      transform={(item) => {
        const l = item as Lead;
        return {
          id: l.id,
          primary: l.name + (l.company ? `（${l.company}）` : ''),
          secondary: [l.phone, l.source].filter(Boolean).join(' · ') || '—',
          badge: l.status,
        };
      }}
    />
  );
}

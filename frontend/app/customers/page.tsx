'use client';

import { BusinessList } from '@/components/business-list';
import { customersApi } from '@/lib/api';
import type { Customer } from '@/lib/api';

export default function CustomersPage() {
  return (
    <BusinessList
      title="客户管理"
      searchPlaceholder="搜索姓名/公司/电话…"
      fetchData={(token, search) => customersApi.list(token, search)}
      transform={(item) => {
        const c = item as Customer;
        return {
          id: c.id,
          primary: c.name + (c.company ? `（${c.company}）` : ''),
          secondary: [c.phone, c.email].filter(Boolean).join(' · ') || '—',
          badge: c.status,
        };
      }}
    />
  );
}

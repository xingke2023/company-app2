'use client';

import { BusinessList } from '@/components/business-list';
import { opportunitiesApi } from '@/lib/api';
import type { Opportunity } from '@/lib/api';

export default function OpportunitiesPage() {
  return (
    <BusinessList
      title="商机"
      searchPlaceholder="搜索商机标题…"
      fetchData={(token, search) => opportunitiesApi.list(token, search)}
      transform={(item) => {
        const o = item as Opportunity;
        return {
          id: o.id,
          primary: o.title,
          secondary: `¥${o.amount} · ${o.stage} · ${o.probability}%`,
          badge: o.customer?.name,
        };
      }}
    />
  );
}

'use client';

import { BusinessList } from '@/components/business-list';
import { purchaseOrdersApi } from '@/lib/api';
import type { PurchaseOrder } from '@/lib/api';

export default function PurchaseOrdersPage() {
  return (
    <BusinessList
      title="采购单"
      searchPlaceholder="搜索单号…"
      fetchData={(token, search) => purchaseOrdersApi.list(token, search)}
      transform={(item) => {
        const o = item as PurchaseOrder;
        return {
          id: o.id,
          primary: o.order_no,
          secondary: `¥${o.total_amount}` + (o.supplier?.name ? ` · ${o.supplier.name}` : ''),
          badge: o.status,
        };
      }}
    />
  );
}

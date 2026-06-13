'use client';

import { BusinessList } from '@/components/business-list';
import { salesOrdersApi } from '@/lib/api';
import type { SalesOrder } from '@/lib/api';

export default function SalesOrdersPage() {
  return (
    <BusinessList
      title="销售订单"
      searchPlaceholder="搜索单号或客户名…"
      fetchData={(token, search) => salesOrdersApi.list(token, search)}
      transform={(item) => {
        const o = item as SalesOrder;
        return {
          id: o.id,
          primary: o.order_no,
          secondary: `¥${o.total_amount}` + (o.customer?.name ? ` · ${o.customer.name}` : ''),
          badge: o.status,
        };
      }}
    />
  );
}

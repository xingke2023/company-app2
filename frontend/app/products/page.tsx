'use client';

import { BusinessList } from '@/components/business-list';
import { productsApi } from '@/lib/api';
import type { Product } from '@/lib/api';

export default function ProductsPage() {
  return (
    <BusinessList
      title="商品管理"
      searchPlaceholder="搜索商品名或SKU…"
      fetchData={(token, search) => productsApi.list(token, search)}
      transform={(item) => {
        const p = item as Product;
        const low = p.min_stock !== null && p.stock_quantity <= (p.min_stock ?? 0) ? ' ⚠️低库存' : '';
        return {
          id: p.id,
          primary: `${p.name}（${p.sku}）${low}`,
          secondary: `库存 ${p.stock_quantity}${p.unit} · 售价 ¥${p.sell_price}`,
          badge: p.status === 'active' ? undefined : p.status,
        };
      }}
    />
  );
}

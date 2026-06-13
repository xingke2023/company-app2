<?php

namespace Database\Seeders;

use App\Models\QuickAction;
use Illuminate\Database\Seeder;

class QuickActionSeeder extends Seeder
{
    /**
     * 小程序「进销存及CRM」行业的底部菜单。
     * 按钮为 open 类型 → report.js web-view，带 app2 JWT ?token= 打开 app2 前端 H5 页（参考 paper 模式）。
     * 幂等：以 key 为唯一键，updateOrCreate 确保 action_type/target_path 正确。
     */
    public function run(): void
    {
        // 0. 返回主页（回小程序行业选择落地页）
        $this->upsert([
            'key' => 'home', 'emoji' => '🏠', 'label' => '返回主页',
            'action_type' => 'home', 'target_path' => '/pages/industry/industry', 'sort_order' => 1,
        ]);

        // 1. 商品管理 → app2 前端 H5 页（report.js 自动带 app2 JWT）
        $this->upsert([
            'key' => 'products', 'emoji' => '📦', 'label' => '商品管理',
            'action_type' => 'open', 'target_path' => '/products', 'target_title' => '商品管理', 'sort_order' => 10,
        ]);

        // 2. 销售订单
        $this->upsert([
            'key' => 'sales-orders', 'emoji' => '🧾', 'label' => '销售订单',
            'action_type' => 'open', 'target_path' => '/sales-orders', 'target_title' => '销售订单', 'sort_order' => 20,
        ]);

        // 3. 采购订单
        $this->upsert([
            'key' => 'purchase-orders', 'emoji' => '🚚', 'label' => '采购订单',
            'action_type' => 'open', 'target_path' => '/purchase-orders', 'target_title' => '采购订单', 'sort_order' => 30,
        ]);

        // 4. CRM 客户管理（menu 分组：客户/线索/商机）
        $crm = $this->upsert([
            'key' => 'crm', 'emoji' => '👥', 'label' => 'CRM 客户',
            'action_type' => 'menu', 'sort_order' => 40,
        ]);
        if ($crm) {
            $subs = [
                ['emoji' => '🧑', 'label' => '客户', 'desc' => '客户档案', 'item_type' => 'route',
                    'route' => '/pages/report/report?path=%2Fcustomers&title=%E5%AE%A2%E6%88%B7%E7%AE%A1%E7%90%86', 'sort_order' => 10],
                ['emoji' => '🎯', 'label' => '线索', 'desc' => '销售线索', 'item_type' => 'route',
                    'route' => '/pages/report/report?path=%2Fleads&title=%E9%94%80%E5%94%AE%E7%BA%BF%E7%B4%A2', 'sort_order' => 20],
                ['emoji' => '💼', 'label' => '商机', 'desc' => '销售机会', 'item_type' => 'route',
                    'route' => '/pages/report/report?path=%2Fopportunities&title=%E9%94%80%E5%94%AE%E5%95%86%E6%9C%BA', 'sort_order' => 30],
            ];
            foreach ($subs as $sub) {
                $crm->items()->updateOrCreate(['label' => $sub['label']], $sub);
            }
        }
    }

    private function upsert(array $attrs): ?QuickAction
    {
        return QuickAction::query()->updateOrCreate(['key' => $attrs['key']], $attrs);
    }
}

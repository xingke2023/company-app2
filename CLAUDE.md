# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Architecture

Monorepo with two independent applications:

- **`backend/`** — Laravel 13 (PHP 8.4), dual-purpose: REST API + Filament admin panel
- **`frontend/`** — Next.js 16 (React 19, TypeScript), App Router, Tailwind CSS 4

### Two Auth Systems (important distinction)

The backend runs **two separate authentication systems** that must not be confused:

| System | Guard / Middleware | Route prefix | Used by |
|--------|-------|--------------|---------|
| JWT + API Key (`auth.hybrid`) | `ApiKeyOrJwtAuth` | `/api/` | Next.js frontend & external services |
| Session (Filament built-in) | `web` | `/admin` | Filament admin panel only |

**`auth.hybrid`** middleware (`app/Http/Middleware/ApiKeyOrJwtAuth.php`) accepts either:
- `Authorization: Bearer <jwt_token>` — standard JWT flow
- `X-API-Key: <api_key>` — permanent key for external/service accounts

JWT flow: `POST /api/auth/login` → `{ access_token, token_type, expires_in, user }` → stored in `localStorage` → sent as `Authorization: Bearer {token}`.

Filament session auth is wired in `app/Providers/Filament/AdminPanelProvider.php`. **Always use `auth('api')`** in API controllers — the default `auth()` uses the `web` guard.

### Domain Model — ERP + CRM

The backend implements two integrated systems sharing the `Customer` model:

**进销存 (Inventory/ERP)**
- `ProductCategory` → `Product` (has category, supplier, stock tracking)
- `Supplier` → `PurchaseOrder` → `PurchaseOrderItem` → `Product`
- `Customer` → `SalesOrder` → `SalesOrderItem` → `Product`
- `InventoryLog` — polymorphic stock movement log (reference_type/reference_id)

**CRM**
- `Customer` (shared with ERP) — has `Contact`s, `SalesOrder`s, `Opportunity`s, `Activity`s
- `Lead` → `Opportunity` (lead_id FK) → can be converted to a Customer
- `Activity` — polymorphic tracking (customer_id / lead_id / opportunity_id, all nullable)

Key integration: `SalesOrder.customer_id` → `Customer`; `Opportunity` can link to both `Customer` and `Lead`.

### Filament Admin Resources

Located at `/admin`. Resources are **modular** — each resource lives in its own subdirectory under `app/Filament/Resources/`, with separate `Schemas/` and `Tables/` classes:

```
app/Filament/Resources/
  Products/
    ProductResource.php       ← navigation group, relations, pages
    Schemas/ProductForm.php   ← form fields (configure() static method)
    Tables/ProductsTable.php  ← table columns, filters, actions
    Pages/...
  PurchaseOrders/
    RelationManagers/ItemsRelationManager.php
  ...
```

Two navigation groups: **进销存** (ProductCategory, Supplier, Product, PurchaseOrder, SalesOrder) and **CRM** (Customer, Lead, Opportunity, Activity). Legacy `UserResource` and `PostResource` are flat files (old style, no subdirectory).

**Filament 5.x property type caveat**: `$navigationGroup` must be declared as `string | \UnitEnum | null` (not `?string`) to match the parent trait. `$navigationIcon` uses `string | BackedEnum | null`. Using `?string` for `$navigationGroup` causes a fatal PHP error.

**RelationManagers** registered on PurchaseOrder (items), SalesOrder (items), Customer (contacts).

### API Routes (`routes/api.php`)

公开接口（无需认证，供小程序免登录调用）：
```
GET    /api/quick-actions     小程序快捷按钮配置（QuickAction + items 树）
GET    /api/app-config        应用配置键值对（AppSetting，品牌文案等）
POST   /api/ai/message        AI 聊天助手（进销存/CRM 自然语言操作）
POST   /api/auth/register
POST   /api/auth/login
```

需认证（`auth.hybrid` — JWT Bearer 或 X-API-Key 均可）：
```
GET    /api/auth/me
POST   /api/auth/logout
POST   /api/auth/refresh
GET    /api/auth/api-keys          列出当前用户的 API Keys
POST   /api/auth/api-keys          创建新 API Key（名称必填）
DELETE /api/auth/api-keys/{id}     删除 API Key

GET    /api/products / /{id}       只读，仅返回 status=active
GET    /api/sales-orders / /{id}   只读
GET    /api/purchase-orders / /{id} 只读

CRUD   /api/customers
CRUD   /api/leads
CRUD   /api/opportunities
```

`PostController` 已存在但路由**未注册**，`/api/posts` 暂不可用。

### Frontend API Layer (`frontend/lib/api/`)

- `client.ts` — `ApiClient` class; token passed explicitly to each call (no interceptor)
- `types.ts` — `User`, `Post`, `AuthResponse`, `PaginatedResponse<T>`, etc. (ERP/CRM types not yet added)
- `auth.ts` / `posts.ts` — service functions wrapping `ApiClient`
- `index.ts` — re-exports everything

`auth-context.tsx` at `frontend/lib/auth-context.tsx` — `AuthProvider` + `useAuth` hook; stores token/user in `localStorage` (`auth_token` / `auth_user`); wraps the app in `app/layout.tsx`.

## Development Commands

### Backend

```bash
cd backend

# Start everything (server + queue + logs + vite) — preferred
composer run dev

# Or just the API server
php artisan serve --host=127.0.0.1 --port=8301

# Database
php artisan migrate
php artisan migrate:fresh --seed   # reset + seed (demo@example.com / password)

# Testing (Pest)
composer run test
php artisan test tests/Feature/ExampleTest.php
php artisan test --filter=testName

# Code style — run after any PHP changes
vendor/bin/pint --dirty

# Utilities
php artisan route:list
php artisan tinker
```

### Frontend

```bash
cd frontend

npm run dev      # port 3112, bound to 0.0.0.0
npm run build
npm run lint

# Add shadcn/ui components
npx shadcn@latest add [component-name]
```

## Key Configuration

### Deployment (this server)

- **Domain**: `app2.xingke888.com` (HTTPS, Let's Encrypt，到期 2026-09-09)
- **Backend port**: 8301 — **Frontend port**: 3112
- **nginx config**: `/etc/nginx/sites-available/app2.xingke888.com.conf`
- **Filament admin**: `https://app2.xingke888.com/admin` — credentials: `demo@example.com` / `password`
- **Process manager**: PM2 — `pm2 list` 查看状态，进程名 `app1-backend` / `app1-frontend`

nginx 路径代理规则：`/api`, `/admin`, `/livewire*`, `/filament`, `/storage`, `/up`, `/_boost`, `/fonts`, `/css`, `/js` → backend (8301)；其余全部 → frontend (3112)。

### Process Management (PM2)

```bash
pm2 list                        # 查看所有进程状态
pm2 logs app1-backend           # 后端日志
pm2 logs app1-frontend          # 前端日志
pm2 restart app1-backend        # 重启后端
pm2 restart app1-frontend       # 重启前端
pm2 save                        # 保存进程列表（开机自启）
```

重启后端后需要刷新配置缓存：
```bash
cd backend && php artisan config:cache && php artisan route:cache
```

前端修改环境变量后需重新构建再重启：
```bash
cd frontend && npm run build && pm2 restart app1-frontend
```

### Environment files

- `backend/.env` — `APP_URL=https://app2.xingke888.com`, `FRONTEND_URL=https://app2.xingke888.com` (CORS origin), `JWT_SECRET`, `JWT_ALGO=HS256`, PostgreSQL credentials
- `frontend/.env.local` — `NEXT_PUBLIC_API_URL=https://app2.xingke888.com/api`

### Database

PostgreSQL，数据库 `clawdb1`，用户 `hengtai_app`，密码见 `backend/.env`。无 SQLite fallback。

```bash
# 重置数据库（会清空所有数据）
cd backend && php artisan migrate:fresh --seed
# seed 后默认账号：demo@example.com / password
```

## API 接口规范

**Base URL**: `https://app2.xingke888.com/api`
**认证**: JWT Bearer Token — 登录后每个请求头携带 `Authorization: Bearer <access_token>`
**响应格式**: 全部 `application/json`
**Token 有效期**: 1 小时，过期后调用 `POST /api/auth/refresh` 换新 token

### 认证模块

| 方法 | 路径 | 认证 | 说明 |
|------|------|------|------|
| POST | `/api/auth/register` | 无 | 注册 |
| POST | `/api/auth/login` | 无 | 登录 |
| GET  | `/api/auth/me` | Bearer | 获取当前用户信息 |
| POST | `/api/auth/logout` | Bearer | 登出（服务端失效 token） |
| POST | `/api/auth/refresh` | Bearer | 刷新 token |

注册/登录统一响应：
```json
{ "access_token": "eyJ...", "token_type": "bearer", "expires_in": 3600, "user": { "id": 1, "name": "...", "email": "..." } }
```

注册请求字段：`name`(必填), `email`(必填唯一), `password`(必填 min:8), `password_confirmation`(必填)

### 进销存模块（只读，需认证）

**商品** `GET /api/products` / `GET /api/products/{id}`
- 查询参数：`search`(name/SKU), `category_id`, `per_page`(默认15)
- 只返回 `status=active` 的商品；详情含 category + supplier 关联
- 字段：`id`, `name`, `sku`, `unit`, `sell_price`, `stock_quantity`, `min_stock`, `status`, `category`, `supplier`

**销售订单** `GET /api/sales-orders` / `GET /api/sales-orders/{id}`
- 查询参数：`status`(`pending/confirmed/shipped/completed/cancelled`), `customer_id`, `per_page`
- 详情含：`customer`(id,name,phone,email) + `items[]`(quantity, unit_price, total_price, product{id,name,sku,unit})

### CRM 模块（完整 CRUD，需认证）

所有列表接口支持分页，响应结构：
```json
{ "current_page": 1, "data": [...], "per_page": 15, "total": 100, "last_page": 7 }
```

**客户** `apiResource /api/customers`

查询参数：`search`(name/company/phone), `status`, `per_page`
详情额外加载：`contacts`(联系人列表), `salesOrders`(最近5条)

| 字段 | 类型 | 必填 | 枚举值 |
|------|------|------|--------|
| `name` | string(100) | 是 | — |
| `company` | string(255) | 否 | — |
| `phone` | string(50) | 否 | — |
| `email` | email | 否 | — |
| `address` | string(500) | 否 | — |
| `type` | enum | 否 | `individual` / `company` |
| `source` | enum | 否 | `web` / `referral` / `call` / `exhibition` / `other` |
| `status` | enum | 否 | `active` / `inactive` / `potential` |
| `assigned_to` | int | 否 | 用户 ID |
| `notes` | string | 否 | — |

**线索** `apiResource /api/leads`

查询参数：`search`(name/company), `status`, `per_page`
详情额外加载：`opportunities`, `activities`(最近10条)

字段同客户，但无 `type`/`address`，`status` 枚举为 `new / contacted / qualified / converted / lost`

**商机** `apiResource /api/opportunities`

查询参数：`stage`, `customer_id`, `per_page`
详情额外加载：`customer`(id,name,phone), `lead`(id,name), `activities`(最近10条)

| 字段 | 类型 | 必填 | 枚举值 |
|------|------|------|--------|
| `title` | string(255) | 是 | — |
| `customer_id` | int | 否 | 客户 ID |
| `lead_id` | int | 否 | 线索 ID |
| `amount` | decimal | 否 | — |
| `stage` | enum | 否 | `prospect / proposal / negotiation / won / lost` |
| `probability` | int(0-100) | 否 | 成交概率 % |
| `expected_close_date` | date | 否 | `YYYY-MM-DD` |
| `assigned_to` | int | 否 | 用户 ID |
| `notes` | string | 否 | — |

### 错误响应规范

| HTTP 状态码 | 场景 |
|-------------|------|
| 200 | 成功（GET/PUT） |
| 201 | 创建成功（POST） |
| 204 | 删除成功（DELETE，无响应体） |
| 401 | 未认证或 token 失效 |
| 422 | 请求参数验证失败，`errors` 字段含详细错误 |
| 404 | 资源不存在 |

```json
// 422 示例
{ "message": "The email field is required.", "errors": { "email": ["The email field is required."] } }
```

### 尚未实现的接口（占位）

- `PostController` 已存在但路由**未注册**，`/api/posts` 暂不可用

### AI 聊天助手 API

**入口**：`POST /api/ai/message`（无需认证，单租户公开接口）

**请求**：
```json
{ "text": "用户自然语言输入", "session_id": "可选会话标识" }
```

**响应**：
```json
{ "reply": "助手回复文字", "intent": "识别到的意图", "session_id": "原样返回" }
```

实现文件：
- `app/Http/Controllers/Api/AiAssistantController.php` — 意图路由 + 全部业务方法
- `app/Services/AiService.php` — LLM 调用 + 意图解析（DeepSeek，配置在 `config/ai.php`）

#### 支持的 42 个意图

**进销存 — 商品**

| 意图 | 触发示例 | 说明 |
|------|---------|------|
| `product_query` | "查库存"、"苹果还有多少" | 列出商品/库存，支持关键词筛选，低库存标 ⚠️ |
| `product_add` | "新增商品苹果 售价5元/斤" | 创建商品，自动生成 SKU，支持关联分类/供应商 |
| `product_update` | "把苹果售价改为6元" | 局部修改商品字段（名称/价格/SKU/单位/状态） |
| `product_delete` | "删除商品苹果" | 删除商品 |
| `stock_update` | "把苹果库存改成100" | 直接设定库存数量，写 InventoryLog |
| `inventory_query` | "查苹果库存变动" | 查 InventoryLog 历史（最近20条） |

**进销存 — 供应商**

| 意图 | 触发示例 |
|------|---------|
| `supplier_query` | "查供应商"、"有哪些供应商" |
| `supplier_add` | "新增供应商顺丰 联系人李总 138…" |
| `supplier_update` | "把顺丰联系人改为王总" |
| `supplier_delete` | "删除供应商顺丰" |

**进销存 — 商品分类**

| 意图 | 触发示例 |
|------|---------|
| `category_query` | "有哪些商品分类" |
| `category_add` | "新增分类：生鲜" |
| `category_delete` | "删除分类生鲜" |

**进销存 — 采购单**

| 意图 | 触发示例 | 说明 |
|------|---------|------|
| `purchase_query` | "查采购订单"、"进了什么货" | |
| `purchase_add` | "进货：苹果50个单价3元" | 创建采购单 + 明细 + 自动加库存 + InventoryLog |
| `purchase_update` | "采购单PO… 状态改为已收货" | 修改状态/备注 |
| `purchase_delete` | "删除采购单PO…" | 删除单据（不回退库存） |

**进销存 — 销售单**

| 意图 | 触发示例 | 说明 |
|------|---------|------|
| `sales_query` | "查销售订单"、"张三的订单" | |
| `sales_add` | "销售：张三买了苹果20个8元" | 创建销售单 + 明细 + 自动减库存（不足则拒绝）+ InventoryLog |
| `sales_update` | "销售单SO… 状态改为已发货" | 修改状态，shipped/completed 自动填时间戳 |
| `sales_delete` | "删除销售单SO…" | 删除单据（不回退库存） |

**CRM — 客户**

| 意图 | 触发示例 |
|------|---------|
| `customer_query` | "查客户张三"、"所有客户" |
| `customer_add` | "新增客户张三 138… ABC公司" |
| `customer_update` | "把张三手机改为138…"、"ABC状态改为inactive" |
| `customer_delete` | "删除客户张三" |

**CRM — 线索**

| 意图 | 触发示例 | 说明 |
|------|---------|------|
| `lead_query` | "有哪些线索"、"待跟进线索" | |
| `lead_add` | "录入线索李四 139…" | |
| `lead_update` | "李四线索状态改为qualified"、"线索转化" | 说"转化/赢单" → status:converted |
| `lead_delete` | "删除线索李四" | |

**CRM — 商机**

| 意图 | 触发示例 | 说明 |
|------|---------|------|
| `opportunity_query` | "查商机"、"销售机会" | |
| `opportunity_add` | "新增商机：ABC采购项目 50万" | |
| `opportunity_update` | "ABC商机金额改为80万"、"赢单了" | 说"赢单"→ stage:won；"丢单"→ stage:lost |
| `opportunity_delete` | "删除商机ABC项目" | |

**CRM — 跟进记录**

| 意图 | 触发示例 | 说明 |
|------|---------|------|
| `activity_query` | "查张三的跟进记录" | 支持按客户名/线索名筛选 |
| `activity_add` | "记录对张三的电话跟进"、"给线索李四录入一次会议" | type 自动从"打电话/电话→call、会议→meeting、邮件→email、拜访→visit"推断 |
| `activity_update` | "标记张三电话跟进已完成" | mark_completed:true → 写 completed_at |
| `activity_delete` | "删除跟进记录张三电话" | |

**CRM — 联系人**

| 意图 | 触发示例 |
|------|---------|
| `contact_query` | "ABC公司有哪些联系人" |
| `contact_add` | "给ABC公司添加联系人王五 138… 采购总监" |
| `contact_update` | "把王五的电话改为138…" |
| `contact_delete` | "删除联系人王五" |

**总览**

| 意图 | 触发示例 |
|------|---------|
| `overview_query` | "整体情况"、"经营概况" | 返回商品数/低库存预警/客户数/线索数/商机数/订单数 |

#### 人性化设计说明

- **自然语言容错**：不要求精确格式，"卖了苹果20斤" = sales_add，"赢单" = opportunity_update stage:won
- **缺省值自动补全**：unit 默认"个"，status 默认 active，purchase 状态默认 received
- **自动建档**：purchase_add / stock_update 时商品不存在会自动创建（`findOrCreateProduct` 辅助方法），并在回复中提示"新商品已自动建档"；sales_add 不会自动建档（需已有库存）
- **库存安全**：sales_add 会校验库存，不足自动拒绝并提示当前库存
- **事务保护**：purchase_add / sales_add 在 DB::transaction 内执行，失败全部回滚
- **InventoryLog 自动写入**：stock_update / purchase_add / sales_add 均记录库存变动历史
- **PHP 字符串注意**：提示语中若含中文引号 `"` / `"`，须用单引号字符串拼接，避免被 PHP 解析器误判为字符串闭合（曾出现 ParseError）

## Laravel Patterns in This Codebase

- **No `app/Http/Kernel.php`** — middleware registered in `bootstrap/app.php`; `trustProxies(at: '*')` is set there (required for nginx reverse proxy)
- **Model casts** use `casts(): array` method, not `$casts` property
- **Form Requests** in `app/Http/Requests/Api/` for all API validation (no inline validation in controllers)
- **No Policy classes** — authorization in controllers checks ownership directly
- **`Product.min_stock`** 是 NOT NULL 列，默认值为 `0`（不可传 `null`）
- **小程序配置模型**：`QuickAction` / `QuickActionItem`（底部菜单），`AppSetting`（键值配置）— 仅 Filament 后台管理，无对应写入 API

## Adding a New Filament Resource

```bash
php artisan make:filament-resource ModelName --generate --no-interaction
# Creates: ResourceName/, Schemas/Form.php, Tables/Table.php, Pages/, RelationManagers/
```

Then in the Resource file: set `$navigationGroup` as `string | \UnitEnum | null`, `$navigationLabel`, `$modelLabel`, `$navigationSort`.

## Adding a New API Resource

1. `php artisan make:model Example -mfc --no-interaction` in `backend/`
2. Edit migration, add `$fillable`, define relationships; `php artisan migrate`
3. Create Form Request in `app/Http/Requests/Api/`
4. Add controller in `app/Http/Controllers/Api/`
5. Register in `routes/api.php`
6. Add TypeScript types to `frontend/lib/api/types.ts`
7. Add service functions to `frontend/lib/api/example.ts`, export from `index.ts`

## Technology Versions

- Laravel 13, PHP 8.4, Filament 5.6, JWT-Auth 2.9, Pest 4, Pint 1
- Next.js 16.2, React 19.2, TypeScript 5, Tailwind CSS 4
- react-hook-form + zod for frontend forms
- PostgreSQL (dev & prod)

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Architecture

Monorepo with two independent applications:

- **`backend/`** — Laravel 13 (PHP 8.4), dual-purpose: REST API + Filament admin panel
- **`frontend/`** — Next.js 16 (React 19, TypeScript), App Router, Tailwind CSS 4

### Two Auth Systems (important distinction)

The backend runs **two separate authentication systems** that must not be confused:

| System | Guard | Route prefix | Used by |
|--------|-------|--------------|---------|
| JWT (`php-open-source-saver/jwt-auth`) | `auth:api` | `/api/` | Next.js frontend REST calls |
| Session (Filament built-in) | `web` | `/admin` | Filament admin panel only |

JWT flow: `POST /api/auth/login` → backend returns `{ access_token, token_type, expires_in, user }` → frontend stores in `localStorage` → sent as `Authorization: Bearer {token}` header.

`auth:api` guard is in `config/auth.php`. Filament session auth is wired in `app/Providers/Filament/AdminPanelProvider.php`. **Always use `auth('api')`** in API controllers — the default `auth()` uses the `web` guard.

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

All routes under `auth:api` middleware except the auth endpoints:

```
POST   /api/auth/login|register
GET    /api/auth/me|refresh|logout   (auth:api)

GET    /api/products[/{product}]      (read-only)
GET    /api/sales-orders[/{id}]       (read-only)
CRUD   /api/customers
CRUD   /api/leads
CRUD   /api/opportunities
```

`PostController` exists (`app/Http/Controllers/Api/PostController.php`) but its routes are **not yet registered** in `api.php`.

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

- **Domain**: `app1.xingke888.com` (HTTPS, Cloudflare + Let's Encrypt)
- **Backend port**: 8301 — **Frontend port**: 3112
- **nginx config**: `/etc/nginx/sites-available/app1.xingke888.com.conf`
- **Filament admin**: `https://app1.xingke888.com/admin` — credentials: `demo@example.com` / `password`

The nginx config proxies by path: `/api/`, `/admin`, `/livewire-*`, `/filament`, `/storage`, `/up`, `/_boost`, `/fonts`, `/css`, `/js` → backend (8301). Everything else → frontend (3112).

### Environment files

- `backend/.env` — `APP_URL`, `FRONTEND_URL` (CORS origin), `JWT_SECRET`, `JWT_ALGO=HS256`, PostgreSQL credentials
- `frontend/.env.local` — `NEXT_PUBLIC_API_URL=https://app1.xingke888.com/api`

### Database

PostgreSQL, database `clawdb1`, user `hengtai_app`. No SQLite fallback.

## Laravel Patterns in This Codebase

- **No `app/Http/Kernel.php`** — middleware registered in `bootstrap/app.php`; `trustProxies(at: '*')` is set there (required for nginx reverse proxy)
- **Model casts** use `casts(): array` method, not `$casts` property
- **Form Requests** in `app/Http/Requests/Api/` for all API validation (no inline validation in controllers)
- **No Policy classes** — authorization in controllers checks ownership directly (e.g. `$post->user_id !== $request->user()->id`)

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

# Subscription Lifecycle Engine

A production-grade REST API built with **Laravel 11** for managing the full lifecycle of subscription plans — from creation and pricing through trial periods, payments, renewals, and expiry.

**Repository:** [github.com/MohamedAbuZamil/-Subscription_Lifecycle_Engine](https://github.com/MohamedAbuZamil/-Subscription_Lifecycle_Engine)  
**Contact:** abozamil4204251@gmail.com

---

## Table of Contents

1. [Why Laravel & Sanctum](#why-laravel--sanctum)
2. [Tech Stack](#tech-stack)
3. [Business Logic](#business-logic)
4. [Project Structure](#project-structure)
5. [Database Schema](#database-schema)
6. [Setup — Local Development](#setup--local-development)
7. [Setup — Docker (Database Only)](#setup--docker-database-only)
8. [Running Migrations](#running-migrations)
9. [Running the Server](#running-the-server)
10. [Scheduled Commands (Cron)](#scheduled-commands-cron)
11. [API Endpoints](#api-endpoints)
12. [Testing with REST Client](#testing-with-rest-client)
13. [OpenAPI Specification](#openapi-specification)

---

## Documentation Files

كل الـ docs موجودة في `docs/` folder:

| File | يحتوي على |
|---|---|
| `docs/api.md` | كل endpoint مستقل مع كل الـ request/response shapes لكل سيناريو (201, 200, 401, 403, 404, 409, 422) |
| `docs/openapi.yaml` | OpenAPI 3.0 spec كاملة — استوردها في Swagger UI لتصفح الـ API بصرياً |
| `docs/schema.md` | مرجع سريع لكل field في كل table مع النوع والـ nullable والوصف |
| `docs/cron-jobs.md` | شرح الـ scheduled commands الثلاثة، ترتيبها الليلي، وطريقة إعداد الـ cron في production |
| `docs/testing.md` | دليل اختبار الـ API بـ VS Code REST Client خطوة بخطوة |

**ملفات الـ tests:**

| File | الأداة |
|---|---|
| `tests/api-test.http` | VS Code REST Client — 60 test case |
| `tests/postman-collection.json` | Postman collection — كل الـ endpoints مع أمثلة (مطلوب في الـ submission) |

---

## Why Laravel & Sanctum

### Laravel
- **Expressive ORM (Eloquent):** Clean model relationships and query scopes reduce boilerplate.
- **Form Requests:** Validation logic is isolated per endpoint, keeping controllers slim.
- **API Resources:** Consistent JSON serialization without exposing internal model structure.
- **Scheduler:** Built-in cron scheduling via `routes/console.php` — no external tools needed for background jobs.
- **Middleware:** Role-based access control (`EnsureIsAdmin`) is applied at the route group level.
- **Rate Limiting:** Built-in throttle middleware protects auth and API endpoints.

### Sanctum
- **Token-based auth for APIs:** Each user gets a personal access token on login. No sessions, no cookies — pure stateless API.
- **Lightweight:** Unlike Passport, Sanctum has no OAuth overhead. Perfect for internal APIs and SPAs.
- **Simple revocation:** Tokens are stored in DB and can be deleted per-device or all at once on logout.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 |
| Auth | Laravel Sanctum |
| Database | MySQL 8.0 (via Docker) |
| PHP | 8.3+ |
| Dev Server | PHP built-in server (multi-worker) |
| API Testing | VS Code REST Client (`.http` files) |
| DB Container | Docker Compose |

---

## Business Logic

### Subscription Lifecycle States

```
store()
  ├── plan has trial_days > 0 AND user never had a trial → trialing
  └── otherwise → pending

trialing ──(trial ends, cron 00:00)──────────────────→ pending
pending  ──(markPaid on transaction)─────────────────→ active
active   ──(period ends, auto_renewal=true, balance ok)→ active (renewed)
active   ──(period ends, auto_renewal=true, no balance)→ past_due
active   ──(cancel by user)──────────────────────────→ canceled
active   ──(markPastDue by admin)────────────────────→ past_due
past_due ──(grace period passes, cron 00:10)─────────→ expired
canceled ──(expire by admin)─────────────────────────→ expired
```

### Key Business Rules

- **Currency detection:** Client IP is checked against UAE IP ranges → `AED`; everything else → `USD`. Falls back to USD if no AED price exists for the plan.
- **Free trial:** One free trial per user across ALL plans — not per plan.
- **Cancel:** Only the subscription owner can cancel. Admins cannot cancel on behalf of a user (financial decision).
- **Renew:** System/admin only. Users cannot manually renew.
- **Update subscription:** Admin only. Can only extend `current_period_ends_at`. Cannot change `started_at` or grant extra trial time.
- **Transactions:** Admin creates and manages transactions. `markPaid` auto-activates the linked subscription. `refund` returns the amount to the user's `balance`.
- **Auto-renewal:** Runs nightly. Deducts from user `balance`. If balance is insufficient → subscription goes `past_due`.
- **Pagination limits:** Admin max 100 per page, users max 10 per page.

### Nightly Cron Sequence

```
00:00 — expire-trials    → trialing (trial over) → pending
00:05 — auto-renew       → active (period over)  → renew if balance ok / past_due if not
00:10 — expire-overdue   → past_due (grace over)  → expired
```

---

## Project Structure

```
app/
  Console/Commands/
    AutoRenewSubscriptions.php       ← nightly auto-renewal
    ExpireTrialSubscriptions.php     ← trial → pending
    ExpireOverdueSubscriptions.php   ← past_due → expired
  Http/
    Controllers/
      AuthController.php
      PlanController.php
      PlanPriceController.php
      SubscriptionController.php
      SubscriptionTransactionController.php
    Middleware/
      EnsureIsAdmin.php
    Requests/
      Plan/
      PlanPrice/
      Subscription/
      SubscriptionTransaction/
    Resources/
      PlanResource.php
      PlanPriceResource.php
      SubscriptionResource.php
      SubscriptionTransactionResource.php
  Models/
    User.php
    Plan.php
    PlanPrice.php
    Subscription.php
    SubscriptionTransaction.php

database/
  migrations/          ← raw SQL files (run manually or via Docker init)
  seeders/             ← SQL seeders (admin user)
  init/                ← Docker auto-init folder

docs/
  openapi.yaml         ← full OpenAPI 3.0 specification
  schema.md            ← field-level schema reference
  cron-jobs.md         ← cron setup and command documentation

routes/
  api.php              ← all API routes
  console.php          ← scheduled commands

tests/
  api-test.http        ← VS Code REST Client test file (60 test cases)
```

---

## Database Schema

### users
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `name` | VARCHAR(255) | |
| `email` | VARCHAR(255) UNIQUE | |
| `password` | VARCHAR(255) | bcrypt hashed |
| `is_admin` | TINYINT(1) | default 0 |
| `balance` | DECIMAL(10,2) | default 0.00 — wallet for auto-renewal |
| `auto_renewal` | TINYINT(1) | default 0 — opt-in to automatic renewal |

### plans
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `code` | VARCHAR(50) UNIQUE | e.g. `basic`, `pro` |
| `name` | VARCHAR(255) | |
| `description` | TEXT | nullable |
| `trial_days` | SMALLINT | default 0 |
| `is_active` | TINYINT(1) | default 1 |

### plan_prices
Unique constraint: `(plan_id, billing_cycle, currency)`

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `plan_id` | INT UNSIGNED FK | → plans |
| `billing_cycle` | ENUM | `monthly` / `yearly` |
| `currency` | CHAR(3) | `USD` / `AED` |
| `price` | DECIMAL(10,2) | |
| `grace_period_days` | SMALLINT | days after failed payment before expiry |
| `is_active` | TINYINT(1) | default 1 |
| `external_price_id` | VARCHAR(255) | nullable — for Stripe/PayPal price IDs |

### subscriptions
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK | |
| `plan_id` | INT UNSIGNED FK | |
| `plan_price_id` | INT UNSIGNED FK | |
| `status` | ENUM | `pending`,`trialing`,`active`,`past_due`,`canceled`,`expired` |
| `started_at` | DATETIME | |
| `trial_starts_at` | DATETIME | nullable |
| `trial_ends_at` | DATETIME | nullable |
| `current_period_starts_at` | DATETIME | nullable |
| `current_period_ends_at` | DATETIME | nullable |
| `grace_period_ends_at` | DATETIME | nullable |
| `canceled_at` | DATETIME | nullable |
| `expires_at` | DATETIME | nullable |

### subscription_transactions
| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `subscription_id` | INT UNSIGNED FK | |
| `reference` | VARCHAR(255) UNIQUE | auto-generated `TXN-XXXXXXXXXXXX` |
| `amount` | DECIMAL(10,2) | taken from plan_price |
| `currency` | CHAR(3) | taken from plan_price |
| `status` | ENUM | `pending`,`paid`,`failed`,`refunded` |
| `provider` | VARCHAR(100) | nullable |
| `provider_transaction_id` | VARCHAR(255) | nullable |
| `paid_at` | DATETIME | nullable |
| `failed_at` | DATETIME | nullable |
| `failure_reason` | TEXT | nullable |
| `metadata` | JSON | nullable |

---

## Setup — Local Development

### Prerequisites
- PHP 8.3+
- Composer
- MySQL 8.0 (or Docker)
- Git

### Steps

```bash
# 1. Clone the repository
git clone <repo-url>
cd "Subscription_ Lifecycle_Engine"

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Fill in your DB credentials in .env
#    DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Generate application key
php artisan key:generate

# 6. Run migrations (see section below)

# 7. Start the server
PHP_CLI_SERVER_WORKERS=4 php artisan serve
```

---

## Setup — Docker (Database Only)

The Docker Compose file starts only the MySQL database.

```bash
# Start the DB container
docker-compose up -d

# Wait ~10 seconds for MySQL to be ready, then verify
docker logs subscription_db
```

The container automatically runs all SQL files from `database/init/` on first start.

**Ensure the init folder contains (in order):**
```
database/init/
  01_users.sql
  02_plans.sql
  03_plan_prices.sql
  04_subscriptions.sql
  05_subscription_transactions.sql
  06_personal_access_tokens.sql
  07_add_is_admin.sql
  08_add_balance.sql
  09_seed_admin.sql
```

---

## Running Migrations

Migrations are raw SQL files. Run them in this order:

```bash
# On Linux/Mac
mysql -u app_user -papp_password subscription_engine < database/migrations/users_2026_05_4.sql
mysql -u app_user -papp_password subscription_engine < database/migrations/plans_2026_05_4.sql
mysql -u app_user -papp_password subscription_engine < database/migrations/plan_prices_2026_05_4.sql
mysql -u app_user -papp_password subscription_engine < database/migrations/subscriptions_2026_05_4.sql
mysql -u app_user -papp_password subscription_engine < database/migrations/subscription_transactions_2026_05_4.sql
mysql -u app_user -papp_password subscription_engine < database/migrations/personal_access_tokens_2026_05_4.sql
mysql -u app_user -papp_password subscription_engine < database/migrations/add_is_admin_to_users_2026_05_4.sql
mysql -u app_user -papp_password subscription_engine < database/migrations/add_balance_to_users_2026_05_4.sql

# Seed the admin user
mysql -u app_user -papp_password subscription_engine < database/seeders/create_default_admin_2026_05_4.sql
```

**On Windows (PowerShell):**
```powershell
Get-Content "database\migrations\users_2026_05_4.sql" | mysql -u app_user -papp_password subscription_engine
# Repeat for each file in order
```

**Default admin credentials:**
- Email: `admin@admin.com`
- Password: `admin123`

---

## Running the Server

```bash
# Start with 4 workers (prevents single-thread hanging)
PHP_CLI_SERVER_WORKERS=4 php artisan serve

# Server runs at: http://localhost:8000/api
```

> **Note:** The PHP built-in server is single-threaded by default. Always use `PHP_CLI_SERVER_WORKERS=4` during development to prevent request hanging when multiple requests occur.

---

## Scheduled Commands (Cron)

See `docs/cron-jobs.md` for full details.

**Production crontab entry (one line):**
```cron
* * * * * cd /var/www/your-project && php artisan schedule:run >> /dev/null 2>&1
```

**Run manually in development:**
```bash
php artisan subscriptions:expire-trials    # trialing → pending
php artisan subscriptions:auto-renew       # deduct balance, renew or past_due
php artisan subscriptions:expire-overdue   # past_due → expired
```

---

## API Endpoints

Base URL: `http://localhost:8000/api`

### Authentication

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/auth/register` | None | Register a new user |
| POST | `/auth/login` | None | Login and receive token |
| POST | `/auth/logout` | Token | Revoke current token |

Rate limit: 5 requests/minute on auth endpoints.

---

### Plans

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/plans/public` | None | Active plans only (public) |
| GET | `/plans` | Token | Paginated plans (admin: all, user: active) |
| GET | `/plans/{id}` | Admin | Full plan details |
| GET | `/plans/{id}/details` | Token | User-visible plan details |
| POST | `/plans` | Admin | Create plan |
| PUT | `/plans/{id}` | Admin | Full replace |
| PATCH | `/plans/{id}` | Admin | Partial update |
| DELETE | `/plans/{id}` | Admin | Delete (fails if has prices) |

---

### Plan Prices

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/plan-prices` | Token | Paginated prices |
| GET | `/plan-prices/{id}` | Token | Single price |
| POST | `/plan-prices` | Admin | Create price (unique per plan+cycle+currency, plan must be active) |
| PUT | `/plan-prices/{id}` | Admin | Full replace |
| PATCH | `/plan-prices/{id}` | Admin | Partial update |
| DELETE | `/plan-prices/{id}` | Admin | Delete (fails if has subscriptions) |

---

### Subscriptions

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/subscriptions` | Token | List (admin: all+filters, user: own, max 10) |
| POST | `/subscriptions` | Token | Create — auto-detects currency from IP, applies trial if eligible |
| GET | `/subscriptions/{id}` | Token | Show (own or admin) |
| PUT | `/subscriptions/{id}` | Admin | Extend `current_period_ends_at` only |
| POST | `/subscriptions/{id}/activate` | Admin | pending/trialing/past_due → active |
| POST | `/subscriptions/{id}/cancel` | Owner | active/trialing/past_due → canceled |
| POST | `/subscriptions/{id}/renew` | Admin | Advance billing period |
| POST | `/subscriptions/{id}/mark-past-due` | Admin | active → past_due + grace period |
| POST | `/subscriptions/{id}/expire` | Admin | past_due/canceled → expired |

---

### Subscription Transactions

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/subscription-transactions` | Token | List (admin: all+filters, user: own, max 10) |
| POST | `/subscription-transactions` | Admin | Create pending transaction (amount/currency from plan_price) |
| GET | `/subscription-transactions/{id}` | Token | Show (own or admin) |
| POST | `/subscription-transactions/{id}/mark-paid` | Admin | paid + auto-activates subscription |
| POST | `/subscription-transactions/{id}/mark-failed` | Admin | failed + failure_reason |
| POST | `/subscription-transactions/{id}/refund` | Admin | refunded + restores user balance |

---

## Testing with REST Client

Open `tests/api-test.http` in VS Code with the **REST Client** extension installed.

### Setup
1. Start the server: `PHP_CLI_SERVER_WORKERS=4 php artisan serve`
2. Open `tests/api-test.http`
3. Run `[1] Register normal user` and `[3] Login as admin`
4. Copy the returned tokens into the variables at the top of the file:

```
@adminToken = 7|your-admin-token-here
@userToken  = 5|your-user-token-here
```

### Test Cases Summary

| # | Request | Expected |
|---|---|---|
| 1 | Register user | 201 |
| 2 | Register duplicate | 422 |
| 3 | Login as admin | 200 + token |
| 4 | Login wrong password | 401 |
| 5 | Logout | 200 |
| 6 | Get plans (no auth) | 401 |
| 7–9 | Plan reads (no auth) | 401 |
| 10–13 | Plan reads/writes (user token) | 200 / 403 |
| 14 | Create plan (admin) | 201 |
| 15 | Create duplicate plan (admin) | 422 |
| 16–32 | Full Plan + PlanPrice CRUD | various |
| 33–34 | Subscription (no auth) | 401 |
| 35 | Create subscription (user, first time) | 201 — status: trialing |
| 36 | Create subscription (already active) | 409 |
| 37–38 | List/show subscriptions (user) | 200 |
| 39 | Cancel subscription (owner) | 200 |
| 40 | Cancel already canceled | 422 |
| 41 | Cancel other user's subscription | 403 |
| 42–49 | Admin subscription operations | various |
| 50 | Get transactions (no auth) | 401 |
| 51 | Create transaction (admin) | 201 |
| 52 | Create transaction (user) | 403 |
| 53–54 | List/show transactions | 200 |
| 55 | Mark paid (auto-activates subscription) | 200 |
| 56 | Mark paid again | 422 |
| 57 | Mark failed (already paid) | 422 |
| 58 | Refund (returns to balance) | 200 |
| 59 | Refund again | 422 |
| 60 | List transactions (user) | 200 (max 10) |

### Simulating UAE Currency
Add this header to any subscription `store` request to get AED pricing:
```
X-Country: AE
```

---

## OpenAPI Specification

The full OpenAPI 3.0 spec is at `docs/openapi.yaml`.

**View interactively:**
- Paste contents into [editor.swagger.io](https://editor.swagger.io)
- Or use the Swagger Viewer VS Code extension

**Key additions vs. original spec:**
- `User.balance` — wallet balance for auto-renewal deductions
- `User.auto_renewal` — boolean opt-in for automatic renewal
- `POST /subscriptions` request simplified to `{plan_id, billing_cycle}` — currency and price auto-detected from IP
- `POST /subscription-transactions` request simplified to `{subscription_id}` — amount/currency/reference auto-generated
- Nightly scheduled commands documented in `docs/cron-jobs.md`

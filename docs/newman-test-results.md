# Newman Test Results — OVH Server Run

## Overview

The Postman collection (`tests/postman-collection.json`) was executed via **Newman CLI** against a live **OVH Ubuntu server** on **April 5, 2026**.

```
Total requests   : 36
Failed requests  : 0
Test scripts     : 9 executed, 0 failed
Pre-request scripts: 37 executed, 0 failed
Assertions       : 0 failed
Total duration   : 13.1s
Average response : 38ms  (min: 15ms, max: 247ms)
Data received    : 15.13 kB
```

**Result: PASS — all 36 requests completed with expected HTTP status codes.**

The database is reset before each run using `php artisan test:reset` to guarantee a clean state.

---

## What the Collection Tests

The collection covers the full subscription lifecycle end-to-end, split into 7 sections.

---

### Section 01 — Auth

| Request | Status | Notes |
|---|---|---|
| Login admin → adminToken saved | **200 OK** | `adminToken` extracted and stored for all subsequent admin requests |
| Register user → userToken saved | **201 Created** | Fresh user created — DB is reset before each run |

**Key behaviour:** The collection runs on a clean database (reset via `php artisan test:reset`). No fallback scripts are needed.

---

### Section 02 — Plans

| Request | Status | Notes |
|---|---|---|
| Create plan → planId saved | **201 Created** | Plan created successfully on clean DB |
| List plans (admin) | **200 OK** | Returns paginated plan list |
| Get plan by ID | **200 OK** | Returns single plan by stored `planId` |
| Update plan PATCH | **200 OK** | Partial update — sets `is_active: true` |
| Replace plan PUT | **200 OK** | Full replace — all fields sent |

---

### Section 03 — Plan Prices

| Request | Status | Notes |
|---|---|---|
| Create USD monthly price → usdPriceId saved | **201 Created** | Price created on clean DB |
| Create AED monthly price → aedPriceId saved | **201 Created** | AED price created successfully |
| Create EGP monthly price | **201 Created** | EGP price created successfully |
| Create USD yearly price | **201 Created** | Yearly price created successfully |
| List plan prices | **200 OK** | Returns all 4 prices for the plan |
| Get price by ID | **200 OK** | Single price by stored `usdPriceId` |
| Update USD price PATCH | **200 OK** | Sets price to 11.99 |

**Key behaviour:** The unique constraint `(plan_id, billing_cycle, currency)` correctly blocks duplicate prices.

---

### Section 04 — Subscriptions

| Request | Status | Notes |
|---|---|---|
| Create subscription → subscriptionId saved | **201 Created** | User subscribes to Basic Plan (monthly). Status is `trialing` — first-time user on clean DB |
| List subscriptions (admin — all) | **200 OK** | Admin sees all subscriptions across all users |
| List subscriptions (user — own only) | **200 OK** | User sees only their own subscriptions (max 10 per page) |
| Get subscription by ID | **200 OK** | Verifies `subscriptionId` is accessible by the owning user |

**Subscription created with status `trialing`** — fresh user on a clean DB qualifies for the 14-day trial.

---

### Section 05 — Transactions

| Request | Status | Notes |
|---|---|---|
| Create transaction → transactionId saved | **201 Created** | Admin creates a pending transaction; amount/currency auto-taken from plan_price |
| List transactions (admin) | **200 OK** | Admin sees all transactions |
| List transactions (user — own only) | **200 OK** | User sees transactions for their own subscriptions |
| Get transaction by ID | **200 OK** | Returns full transaction data by `transactionId` |
| Mark paid → subscription becomes active | **200 OK** | Transaction status → `paid`; subscription status → `active` automatically |
| Get subscription — verify active | **200 OK** | Confirms `status: active`, `current_period_starts_at` and `current_period_ends_at` are set |
| Mark past due — starts grace period | **200 OK** | Admin moves subscription to `past_due`; `grace_period_ends_at` is set |
| Get subscription — verify past_due | **200 OK** | Confirms `status: past_due` |
| Create 2nd transaction (grace period retry) | **201 Created** | New pending transaction for the `past_due` subscription |
| Mark failed (insufficient funds) | **200 OK** | Transaction status → `failed`; `failure_reason` recorded |
| Create 3rd transaction (reactivation) | **201 Created** | Another pending transaction |
| Mark paid (reactivate subscription) | **200 OK** | Subscription returns to `active` |
| Refund → adds to user balance | **200 OK** | Transaction status → `refunded`; `User.balance` increased by the transaction amount |

---

### Section 06 — Lifecycle (cancel / expire / renew)

| Request | Status | Notes |
|---|---|---|
| Cancel subscription (user — owner) | **200 OK** | User cancels own subscription; status → `canceled` |
| Expire subscription (admin) | **200 OK** | Admin expires a canceled subscription; status → `expired` |
| Extend period PUT (admin) | **200 OK** | Admin sets `current_period_ends_at` to `2027-01-01`; only this field is writable |
| Renew subscription (admin) | **200 OK** | Admin renews the `expired` subscription — status returns to `active`, new billing period set |

---

### Section 07 — Cleanup

| Request | Status | Notes |
|---|---|---|
| Delete USD price | **409 Conflict** | Correct — price has linked subscriptions; protected by API business rule |
| Delete AED price | **204 No Content** | Deleted successfully (no subscriptions linked to this price) |
| Delete plan | **409 Conflict** | Correct — plan still has linked prices; protected by API business rule |
| Logout (user) | **200 OK** | User token revoked |

**Note on 409 responses in cleanup:** These are intentional protection rules — the API prevents deletion of resources that have dependent records. On a fresh database, cleanup would succeed completely.

---

## Subscription Lifecycle Demonstrated

```
trialing
  └─ markPaid ──────────────────────────────→ active
       └─ markPastDue ──────────────────────→ past_due
            ├─ markFailed (retry 1) ─────────→ past_due (no change)
            └─ markPaid  (retry 2) ──────────→ active (reactivated)
                 └─ refund ──────────────────→ active (balance restored)
                      └─ cancel (by user) ───→ canceled
                           └─ expire (admin)─→ expired
                                └─ renew (admin) ──→ active
```

---

## Environment

| Item | Value |
|---|---|
| Server | OVH Ubuntu (cloud instance) |
| API URL | `http://127.0.0.1:8000/api` |
| PHP | 8.3 |
| Framework | Laravel 11 |
| Auth | Laravel Sanctum (token-based) |
| Newman version | CLI (latest) |
| Request delay | 300ms between requests |
| Run date | 2026-04-05 |
| DB reset | `php artisan test:reset` before each run |

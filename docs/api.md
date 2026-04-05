# API Reference — Subscription Lifecycle Engine

Full endpoint documentation with all request/response shapes and error scenarios.

Base URL: `http://localhost:8000/api`

---

## Table of Contents

- [Authentication](#authentication)
- [Plans](#plans)
- [Plan Prices](#plan-prices)
- [Subscriptions](#subscriptions)
- [Subscription Transactions](#subscription-transactions)

---

## Authentication

### POST /auth/register

Register a new user account.

**Request:**
```json
{
  "name": "Ahmed Ali",
  "email": "ahmed@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**201 Created:**
```json
{
  "data": {
    "id": 3,
    "name": "Ahmed Ali",
    "email": "ahmed@example.com"
  },
  "token": "5|abc123xyz"
}
```

**422 Unprocessable Entity** — email already taken:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

**422 Unprocessable Entity** — password mismatch:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "password": ["The password confirmation does not match."]
  }
}
```

---

### POST /auth/login

Login and receive a bearer token.

**Request:**
```json
{
  "email": "ahmed@example.com",
  "password": "password123"
}
```

**200 OK:**
```json
{
  "data": {
    "id": 3,
    "name": "Ahmed Ali",
    "email": "ahmed@example.com"
  },
  "token": "5|abc123xyz"
}
```

**401 Unauthorized** — wrong credentials:
```json
{
  "message": "Invalid credentials."
}
```

---

### POST /auth/logout

Revoke the current access token.

**Headers:** `Authorization: Bearer {token}`

**200 OK:**
```json
{
  "message": "Logged out successfully."
}
```

**401 Unauthorized** — no token:
```json
{
  "message": "Unauthenticated."
}
```

---

## Plans

### GET /plans/public

Public list of active plans. No authentication required.

**200 OK:**
```json
{
  "data": [
    {
      "id": 1,
      "code": "basic",
      "name": "Basic Plan",
      "description": "Entry-level plan",
      "trial_days": 14,
      "is_active": true,
      "created_at": "2026-03-01T00:00:00Z",
      "updated_at": "2026-03-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

---

### GET /plans

Paginated list of plans.

**Headers:** `Authorization: Bearer {token}`

**Query params:** `per_page` (admin: max 100, user: max 10), `is_active`

**200 OK (admin — sees all):**
```json
{
  "data": [
    {
      "id": 1,
      "code": "basic",
      "name": "Basic Plan",
      "description": "Entry-level plan",
      "trial_days": 14,
      "is_active": true,
      "created_at": "2026-03-01T00:00:00Z",
      "updated_at": "2026-03-01T00:00:00Z"
    },
    {
      "id": 2,
      "code": "pro",
      "name": "Pro Plan",
      "description": null,
      "trial_days": 7,
      "is_active": false,
      "created_at": "2026-03-01T00:00:00Z",
      "updated_at": "2026-03-14T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 2,
    "last_page": 1
  }
}
```

**401 Unauthorized** — no token:
```json
{ "message": "Unauthenticated." }
```

---

### GET /plans/{id}

**Headers:** `Authorization: Bearer {adminToken}`

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "code": "basic",
    "name": "Basic Plan",
    "description": "Entry-level plan",
    "trial_days": 14,
    "is_active": true,
    "created_at": "2026-03-01T00:00:00Z",
    "updated_at": "2026-03-01T00:00:00Z"
  }
}
```

**403 Forbidden** — non-admin token:
```json
{ "message": "Forbidden." }
```

**404 Not Found:**
```json
{ "message": "Not found." }
```

---

### POST /plans

**Headers:** `Authorization: Bearer {adminToken}`

**Request:**
```json
{
  "code": "enterprise",
  "name": "Enterprise Plan",
  "description": "Best for large teams",
  "trial_days": 30,
  "is_active": true
}
```

**201 Created:**
```json
{
  "data": {
    "id": 3,
    "code": "enterprise",
    "name": "Enterprise Plan",
    "description": "Best for large teams",
    "trial_days": 30,
    "is_active": true,
    "created_at": "2026-04-01T00:00:00Z",
    "updated_at": "2026-04-01T00:00:00Z"
  }
}
```

**409 Conflict** — duplicate code:
```json
{ "message": "A plan with this code already exists." }
```

**422 Unprocessable Entity** — missing required fields:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "code": ["The code field is required."],
    "name": ["The name field is required."]
  }
}
```

**403 Forbidden** — non-admin:
```json
{ "message": "Forbidden." }
```

---

### PATCH /plans/{id}

**Headers:** `Authorization: Bearer {adminToken}`

**Request:**
```json
{ "is_active": false }
```

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "code": "basic",
    "name": "Basic Plan",
    "description": "Entry-level plan",
    "trial_days": 14,
    "is_active": false,
    "created_at": "2026-03-01T00:00:00Z",
    "updated_at": "2026-04-05T20:00:00Z"
  }
}
```

---

### DELETE /plans/{id}

**Headers:** `Authorization: Bearer {adminToken}`

**204 No Content** — deleted successfully (empty body)

**409 Conflict** — plan has prices linked:
```json
{ "message": "Cannot delete a plan that has prices." }
```

**404 Not Found:**
```json
{ "message": "Not found." }
```

---

## Plan Prices

### GET /plan-prices

**Headers:** `Authorization: Bearer {token}`

**Query params:** `plan_id`, `billing_cycle`, `currency`, `is_active`, `per_page`

**200 OK:**
```json
{
  "data": [
    {
      "id": 1,
      "plan_id": 1,
      "billing_cycle": "monthly",
      "currency": "USD",
      "price": "9.99",
      "grace_period_days": 3,
      "is_active": true,
      "external_price_id": null,
      "created_at": "2026-03-01T00:00:00Z",
      "updated_at": "2026-03-01T00:00:00Z"
    },
    {
      "id": 2,
      "plan_id": 1,
      "billing_cycle": "monthly",
      "currency": "AED",
      "price": "36.99",
      "grace_period_days": 3,
      "is_active": true,
      "external_price_id": null,
      "created_at": "2026-03-01T00:00:00Z",
      "updated_at": "2026-03-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 2,
    "last_page": 1
  }
}
```

---

### POST /plan-prices

**Headers:** `Authorization: Bearer {adminToken}`

**Request:**
```json
{
  "plan_id": 1,
  "billing_cycle": "monthly",
  "currency": "AED",
  "price": 36.99,
  "grace_period_days": 5,
  "is_active": true
}
```

**201 Created:**
```json
{
  "data": {
    "id": 3,
    "plan_id": 1,
    "billing_cycle": "monthly",
    "currency": "AED",
    "price": "36.99",
    "grace_period_days": 5,
    "is_active": true,
    "external_price_id": null,
    "created_at": "2026-04-01T00:00:00Z",
    "updated_at": "2026-04-01T00:00:00Z"
  }
}
```

**409 Conflict** — duplicate (plan_id + billing_cycle + currency):
```json
{ "message": "A price for this plan, billing cycle, and currency already exists." }
```

**422 Unprocessable Entity** — plan is inactive:
```json
{ "message": "Cannot add a price to an inactive plan." }
```

**422 Unprocessable Entity** — validation:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "price": ["The price must be greater than 0."],
    "billing_cycle": ["The selected billing cycle is invalid."]
  }
}
```

---

### DELETE /plan-prices/{id}

**204 No Content** — deleted successfully

**409 Conflict** — price has subscriptions:
```json
{ "message": "Cannot delete a price that has active subscriptions." }
```

---

## Subscriptions

### GET /subscriptions

**Headers:** `Authorization: Bearer {token}`

**Query params (admin only):** `user_id`, `status`, `per_page` (admin max 100, user max 10)

**200 OK (user — sees only own):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 3,
      "plan_id": 1,
      "plan_price_id": 2,
      "status": "trialing",
      "started_at": "2026-04-05T20:00:00Z",
      "trial_starts_at": "2026-04-05T20:00:00Z",
      "trial_ends_at": "2026-04-19T20:00:00Z",
      "current_period_starts_at": null,
      "current_period_ends_at": null,
      "grace_period_ends_at": null,
      "canceled_at": null,
      "expires_at": null,
      "created_at": "2026-04-05T20:00:00Z",
      "updated_at": "2026-04-05T20:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1
  }
}
```

---

### POST /subscriptions

Create a new subscription. Auth user only.

**Headers:** `Authorization: Bearer {userToken}`

**Request:**
```json
{
  "plan_id": 1,
  "billing_cycle": "monthly"
}
```

**201 Created — with free trial (first time):**
```json
{
  "data": {
    "id": 1,
    "user_id": 3,
    "plan_id": 1,
    "plan_price_id": 2,
    "status": "trialing",
    "started_at": "2026-04-05T20:00:00Z",
    "trial_starts_at": "2026-04-05T20:00:00Z",
    "trial_ends_at": "2026-04-19T20:00:00Z",
    "current_period_starts_at": null,
    "current_period_ends_at": null,
    "grace_period_ends_at": null,
    "canceled_at": null,
    "expires_at": null,
    "created_at": "2026-04-05T20:00:00Z",
    "updated_at": "2026-04-05T20:00:00Z"
  }
}
```

**201 Created — no trial (already used or plan has trial_days=0):**
```json
{
  "data": {
    "id": 2,
    "user_id": 3,
    "plan_id": 1,
    "plan_price_id": 2,
    "status": "pending",
    "started_at": "2026-04-05T20:00:00Z",
    "trial_starts_at": null,
    "trial_ends_at": null,
    "current_period_starts_at": null,
    "current_period_ends_at": null,
    "grace_period_ends_at": null,
    "canceled_at": null,
    "expires_at": null,
    "created_at": "2026-04-05T20:00:00Z",
    "updated_at": "2026-04-05T20:00:00Z"
  }
}
```

**409 Conflict** — already has active/trialing/past_due subscription:
```json
{ "message": "User already has an active subscription." }
```

**422 Unprocessable Entity** — plan is inactive:
```json
{ "message": "This plan is not active." }
```

**422 Unprocessable Entity** — no price found for billing_cycle + currency:
```json
{ "message": "No active price found for this plan and billing cycle." }
```

**401 Unauthorized:**
```json
{ "message": "Unauthenticated." }
```

---

### GET /subscriptions/{id}

**Headers:** `Authorization: Bearer {token}`

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "user_id": 3,
    "plan_id": 1,
    "plan_price_id": 2,
    "status": "active",
    "started_at": "2026-04-05T20:00:00Z",
    "trial_starts_at": "2026-04-05T20:00:00Z",
    "trial_ends_at": "2026-04-19T20:00:00Z",
    "current_period_starts_at": "2026-04-19T20:00:00Z",
    "current_period_ends_at": "2026-05-19T20:00:00Z",
    "grace_period_ends_at": null,
    "canceled_at": null,
    "expires_at": null,
    "created_at": "2026-04-05T20:00:00Z",
    "updated_at": "2026-04-19T20:00:00Z"
  }
}
```

**403 Forbidden** — user viewing another user's subscription:
```json
{ "message": "Forbidden." }
```

**404 Not Found:**
```json
{ "message": "Not found." }
```

---

### PUT /subscriptions/{id}

Admin only — extend `current_period_ends_at`.

**Headers:** `Authorization: Bearer {adminToken}`

**Request:**
```json
{ "current_period_ends_at": "2027-01-01 00:00:00" }
```

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "status": "active",
    "current_period_ends_at": "2027-01-01T00:00:00Z",
    "..."
  }
}
```

**403 Forbidden** — non-admin:
```json
{ "message": "Forbidden." }
```

**422 Unprocessable Entity** — date in the past:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "current_period_ends_at": ["The current period ends at must be a date after now."]
  }
}
```

---

### POST /subscriptions/{id}/activate

Admin only — transitions `pending` / `trialing` / `past_due` → `active`.

**Headers:** `Authorization: Bearer {adminToken}`

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "status": "active",
    "current_period_starts_at": "2026-04-19T20:00:00Z",
    "current_period_ends_at": "2026-05-19T20:00:00Z",
    "grace_period_ends_at": null,
    "trial_ends_at": "2026-04-19T20:00:00Z",
    "..."
  }
}
```

**422 Unprocessable Entity** — wrong status:
```json
{ "message": "Only pending, trialing, or past_due subscriptions can be activated." }
```

---

### POST /subscriptions/{id}/cancel

**Owner only** — admin cannot cancel for a user.

**Headers:** `Authorization: Bearer {userToken}`

**Request (optional):**
```json
{ "reason": "No longer needed." }
```

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "status": "canceled",
    "canceled_at": "2026-04-05T20:30:00Z",
    "..."
  }
}
```

**403 Forbidden** — admin trying to cancel:
```json
{ "message": "Forbidden. Only the subscription owner can cancel." }
```

**403 Forbidden** — different user's subscription:
```json
{ "message": "Forbidden. Only the subscription owner can cancel." }
```

**422 Unprocessable Entity** — already canceled/expired:
```json
{ "message": "Only active, trialing, or past_due subscriptions can be canceled." }
```

---

### POST /subscriptions/{id}/mark-past-due

Admin only — transitions `active` → `past_due` + sets grace period.

**Headers:** `Authorization: Bearer {adminToken}`

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "status": "past_due",
    "grace_period_ends_at": "2026-05-22T20:00:00Z",
    "..."
  }
}
```

**422 Unprocessable Entity:**
```json
{ "message": "Only active subscriptions can be marked as past due." }
```

---

### POST /subscriptions/{id}/renew

Admin/system only — advances billing period.

**Headers:** `Authorization: Bearer {adminToken}`

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "status": "active",
    "current_period_starts_at": "2026-05-19T20:00:00Z",
    "current_period_ends_at": "2026-06-19T20:00:00Z",
    "..."
  }
}
```

**403 Forbidden** — user token:
```json
{ "message": "Forbidden. Renewals are managed by the system." }
```

**422 Unprocessable Entity:**
```json
{ "message": "Only active subscriptions can be renewed." }
```

---

### POST /subscriptions/{id}/expire

Admin only — transitions `past_due` / `canceled` → `expired`.

**Headers:** `Authorization: Bearer {adminToken}`

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "status": "expired",
    "expires_at": "2026-04-05T20:30:00Z",
    "..."
  }
}
```

**422 Unprocessable Entity:**
```json
{ "message": "Only past_due or canceled subscriptions can be expired." }
```

---

## Subscription Transactions

### GET /subscription-transactions

**Headers:** `Authorization: Bearer {token}`

**Query params (admin only):** `subscription_id`, `status`, `per_page`

**200 OK (admin):**
```json
{
  "data": [
    {
      "id": 1,
      "subscription_id": 1,
      "reference": "TXN-ABCDEFGHIJKL",
      "amount": "9.99",
      "currency": "USD",
      "status": "paid",
      "provider": "manual",
      "provider_transaction_id": null,
      "paid_at": "2026-04-19T20:00:00Z",
      "failed_at": null,
      "failure_reason": null,
      "metadata": null,
      "created_at": "2026-04-19T19:55:00Z",
      "updated_at": "2026-04-19T20:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

**200 OK (user — sees only own subscription's transactions, max 10):**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1
  }
}
```

---

### POST /subscription-transactions

Admin only — creates a pending transaction. Amount/currency/reference auto-generated.

**Headers:** `Authorization: Bearer {adminToken}`

**Request:**
```json
{
  "subscription_id": 1,
  "provider": "manual"
}
```

**201 Created:**
```json
{
  "data": {
    "id": 1,
    "subscription_id": 1,
    "reference": "TXN-XKQZMNAPOLRJ",
    "amount": "9.99",
    "currency": "USD",
    "status": "pending",
    "provider": "manual",
    "provider_transaction_id": null,
    "paid_at": null,
    "failed_at": null,
    "failure_reason": null,
    "metadata": null,
    "created_at": "2026-04-05T20:00:00Z",
    "updated_at": "2026-04-05T20:00:00Z"
  }
}
```

**403 Forbidden** — non-admin:
```json
{ "message": "Forbidden." }
```

**422 Unprocessable Entity** — subscription not in pending/trialing/past_due:
```json
{ "message": "Transactions can only be created for pending, trialing, or past_due subscriptions." }
```

**422 Unprocessable Entity** — subscription not found:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "subscription_id": ["The selected subscription id is invalid."]
  }
}
```

---

### GET /subscription-transactions/{id}

**Headers:** `Authorization: Bearer {token}`

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "subscription_id": 1,
    "reference": "TXN-XKQZMNAPOLRJ",
    "amount": "9.99",
    "currency": "USD",
    "status": "pending",
    "provider": "manual",
    "provider_transaction_id": null,
    "paid_at": null,
    "failed_at": null,
    "failure_reason": null,
    "metadata": null,
    "created_at": "2026-04-05T20:00:00Z",
    "updated_at": "2026-04-05T20:00:00Z"
  }
}
```

**403 Forbidden** — user viewing another user's transaction:
```json
{ "message": "Forbidden." }
```

**404 Not Found:**
```json
{ "message": "Not found." }
```

---

### POST /subscription-transactions/{id}/mark-paid

Admin only. Marks transaction as paid and **auto-activates** the linked subscription.

**Headers:** `Authorization: Bearer {adminToken}`

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "subscription_id": 1,
    "reference": "TXN-XKQZMNAPOLRJ",
    "amount": "9.99",
    "currency": "USD",
    "status": "paid",
    "paid_at": "2026-04-19T20:00:00Z",
    "failed_at": null,
    "failure_reason": null,
    "..."
  }
}
```

> **Side effect:** The linked subscription status automatically transitions to `active` with a new billing period calculated from `now` + billing cycle.

**403 Forbidden** — non-admin:
```json
{ "message": "Forbidden." }
```

**422 Unprocessable Entity** — transaction not pending:
```json
{ "message": "Only pending transactions can be marked as paid." }
```

---

### POST /subscription-transactions/{id}/mark-failed

Admin only.

**Headers:** `Authorization: Bearer {adminToken}`

**Request:**
```json
{
  "failure_reason": "Insufficient funds"
}
```

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "status": "failed",
    "failed_at": "2026-04-05T20:05:00Z",
    "failure_reason": "Insufficient funds",
    "..."
  }
}
```

**422 Unprocessable Entity** — not pending:
```json
{ "message": "Only pending transactions can be marked as failed." }
```

---

### POST /subscription-transactions/{id}/refund

Admin only. Refunds the transaction and **adds the amount back to the user's balance**.

**Headers:** `Authorization: Bearer {adminToken}`

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "status": "refunded",
    "amount": "9.99",
    "currency": "USD",
    "..."
  }
}
```

> **Side effect:** `users.balance` is incremented by `transaction.amount`.

**422 Unprocessable Entity** — not paid:
```json
{ "message": "Only paid transactions can be refunded." }
```

---

## Common Error Responses

| Code | Meaning | When |
|---|---|---|
| `401` | Unauthenticated | Missing or invalid bearer token |
| `403` | Forbidden | Authenticated but not authorized (wrong role or not owner) |
| `404` | Not Found | Resource with given ID doesn't exist |
| `409` | Conflict | Duplicate unique field or business rule violation |
| `422` | Unprocessable Entity | Validation failed or business rule violation |
| `500` | Internal Server Error | Unexpected server error |

# Schema Reference

Full field-level reference for all data models in the Subscription Lifecycle Engine API.

---

## Plan

| Field | Type | Nullable | Read-only | Description |
|---|---|---|---|---|
| `id` | integer | No | Yes | Auto-incremented primary key |
| `code` | string | No | No | Unique machine-readable identifier. Lowercase alphanumeric, hyphens allowed |
| `name` | string | No | No | Human-readable display name |
| `description` | string | Yes | No | Optional plan description |
| `trial_days` | integer | No | No | Free trial duration in days. Default: `0` |
| `is_active` | boolean | No | No | Whether the plan is available to new subscribers. Default: `true` |
| `created_at` | datetime | No | Yes | Record creation timestamp |
| `updated_at` | datetime | No | Yes | Last update timestamp |

---

## PlanPrice

| Field | Type | Nullable | Read-only | Description |
|---|---|---|---|---|
| `id` | integer | No | Yes | Auto-incremented primary key |
| `plan_id` | integer | No | No | ID of the parent plan |
| `billing_cycle` | enum | No | No | `monthly` or `yearly` |
| `currency` | string(3) | No | No | ISO 4217 currency code (e.g. `USD`, `AED`) |
| `price` | float | No | No | Charge amount per billing cycle. Must be > 0 |
| `grace_period_days` | integer | No | No | Days after payment failure before subscription expires. Default: `3` |
| `is_active` | boolean | No | No | Whether this price is available for new subscriptions. Default: `true` |
| `external_price_id` | string | Yes | No | Matching price ID from an external payment provider (e.g. Stripe) |
| `created_at` | datetime | No | Yes | Record creation timestamp |
| `updated_at` | datetime | No | Yes | Last update timestamp |

> **Unique constraint:** `(plan_id, billing_cycle, currency)` must be unique.

---

## Subscription

| Field | Type | Nullable | Read-only | Description |
|---|---|---|---|---|
| `id` | integer | No | Yes | Auto-incremented primary key |
| `user_id` | integer | No | No | ID of the subscribing user |
| `plan_id` | integer | No | No | ID of the subscribed plan |
| `plan_price_id` | integer | No | No | ID of the plan price used for billing |
| `status` | enum | No | No | Current lifecycle status. See status table below |
| `started_at` | datetime | No | No | When the subscription was first created |
| `trial_starts_at` | datetime | Yes | No | Start of the trial period. Null if no trial |
| `trial_ends_at` | datetime | Yes | No | End of the trial period. Null if no trial |
| `current_period_starts_at` | datetime | Yes | No | Start of the current paid billing period |
| `current_period_ends_at` | datetime | Yes | No | End of the current paid billing period |
| `grace_period_ends_at` | datetime | Yes | No | Payment deadline when status is `past_due` |
| `canceled_at` | datetime | Yes | No | When the subscription was canceled. Null if not canceled |
| `expires_at` | datetime | Yes | No | When the subscription was manually expired by admin. Null if not expired |
| `created_at` | datetime | No | Yes | Record creation timestamp |
| `updated_at` | datetime | No | Yes | Last update timestamp |

### Subscription Status Values

| Status | Description |
|---|---|
| `pending` | Created but not yet started |
| `trialing` | In an active free trial period |
| `active` | Paid and currently active |
| `past_due` | Payment failed; within the grace period |
| `canceled` | Canceled by the user or system |
| `expired` | Manually expired by admin (separate from grace period cancellation) |

---

## SubscriptionTransaction

| Field | Type | Nullable | Read-only | Description |
|---|---|---|---|---|
| `id` | integer | No | Yes | Auto-incremented primary key |
| `subscription_id` | integer | No | No | ID of the related subscription |
| `reference` | string | No | No | Globally unique transaction reference |
| `amount` | float | No | No | Transaction amount charged. Must be > 0 |
| `currency` | string(3) | No | No | ISO 4217 currency code |
| `status` | enum | No | No | Payment status. See status table below |
| `provider` | string | Yes | No | Payment provider name (e.g. `stripe`, `paypal`) |
| `provider_transaction_id` | string | Yes | No | Transaction ID from the payment provider |
| `paid_at` | datetime | Yes | No | When payment was confirmed. Null until paid |
| `failed_at` | datetime | Yes | No | When the payment failed. Null unless failed |
| `failure_reason` | string | Yes | No | Human-readable reason for payment failure |
| `metadata` | object | Yes | No | Arbitrary JSON from the payment gateway |
| `created_at` | datetime | No | Yes | Record creation timestamp |
| `updated_at` | datetime | No | Yes | Last update timestamp |

### Transaction Status Values

| Status | Description |
|---|---|
| `pending` | Transaction created, awaiting payment |
| `paid` | Payment confirmed successfully |
| `failed` | Payment attempt failed |
| `refunded` | Payment was reversed and refunded |

---

## Error Responses

### ErrorResponse

Returned for `4xx` and `5xx` errors.

| Field | Type | Description |
|---|---|---|
| `message` | string | Human-readable error description |
| `code` | string | Machine-readable error code |

**Common codes:** `NOT_FOUND`, `CONFLICT`, `INTERNAL_SERVER_ERROR`

### ValidationErrorResponse

Returned for `422 Unprocessable Entity`.

| Field | Type | Description |
|---|---|---|
| `message` | string | Top-level validation error summary |
| `errors` | object | Map of field names to arrays of error messages |

---

## Pagination

All list endpoints return a `meta` object alongside the `data` array.

| Field | Type | Description |
|---|---|---|
| `current_page` | integer | Current page number (1-based) |
| `per_page` | integer | Number of items per page |
| `total` | integer | Total number of matching records |
| `last_page` | integer | Last available page number |

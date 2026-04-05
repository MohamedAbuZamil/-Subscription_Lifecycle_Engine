# Testing Guide

How to test the Subscription Lifecycle Engine API using VS Code REST Client.

---

## Prerequisites

Start the server before running any tests:

```bash
PHP_CLI_SERVER_WORKERS=4 php artisan serve
```

Server runs at: `http://localhost:8000/api`

---

## VS Code REST Client

### Setup

1. Install the **REST Client** extension by Humao in VS Code
2. Open `tests/api-test.http`

### Get Tokens

Run **[3] Login as admin** → copy the token from the response panel on the right.

At the top of `api-test.http`, update the variables:

```
@adminToken = 7|your-admin-token-here
@userToken  = 5|your-user-token-here
```

### Run a Request

Click **Send Request** that appears above any `###` block:

```http
### [14] Create plan — admin → 201
POST {{baseUrl}}/plans
Content-Type: application/json
Authorization: Bearer {{adminToken}}

{
  "code": "basic",
  "name": "Basic Plan",
  "trial_days": 14,
  "is_active": true
}
```

Response appears in a split panel on the right.

---

## Recommended Test Order

شغّل الـ tests بالترتيب ده عشان كل request يعتمد على اللي قبله:

```
[1]  Register user
[3]  Login admin         ← كوبي adminToken
[14] Create plan
[21] Create price USD
[22] Create price AED
[35] Create subscription (user → trialing)
[51] Create transaction  (admin)
[55] Mark paid           (admin → subscription becomes active)
[58] Refund              (admin → balance restored)
```

---

## Simulating UAE Currency (AED)

لو عايز تتأكد إن الـ AED price بتتاخد للـ UAE users، أضف الـ header ده في request الـ subscription:

```http
X-Forwarded-For: 94.200.1.1
```

الـ IP ده بيبقى في نطاق الإمارات → الـ controller يشوفه ويختار AED تلقائياً.

---

## Common Issues

| المشكلة | السبب | الحل |
|---|---|---|
| `401 Unauthenticated` | الـ token غلط أو فاضي | Login تاني وحدّث الـ token |
| `403 Forbidden` | بتستخدم userToken في admin endpoint | استخدم `adminToken` |
| `409 Conflict` | الـ subscription موجودة قبل كده | Cancel أو Expire الأول |
| `422` على create transaction | الـ subscription مش في pending/trialing/past_due | تأكد من status الـ subscription |
| Server مش بيرد | الـ server واقف | شغّل `PHP_CLI_SERVER_WORKERS=4 php artisan serve` |

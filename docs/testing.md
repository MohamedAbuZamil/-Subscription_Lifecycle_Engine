# Testing Guide

How to test the Subscription Lifecycle Engine API — either via **Newman CLI** (automated, recommended) or **VS Code REST Client** (manual).

---

## 1 — Automated Testing with Newman (Recommended)

### What was tested

The full Postman collection (`tests/postman-collection.json`) was executed via **Newman CLI** against a live **OVH Ubuntu server** running the API.

All **42 requests** completed successfully with **0 failures**:

| Section | Requests | Result |
|---|---|---|
| 01 — Auth (register / login / logout) | 3 | ✅ |
| 02 — Plans (CRUD + PATCH + PUT) | 5 | ✅ |
| 03 — Plan Prices (create / list / get / patch) | 6 | ✅ |
| 04 — Subscriptions (create / list / get) | 3 | ✅ |
| 05 — Transactions (create / mark-paid / mark-failed / refund) | 9 | ✅ |
| 06 — Lifecycle (cancel / expire / extend / renew) | 4 | ✅ |
| 07 — Cleanup (delete prices / plan / logout) | 4 | ✅ |

The full output is saved in `newman-results.txt` at the project root on the server.

---

### How to re-run the tests yourself

#### Prerequisites

- Node.js installed
- Newman installed globally: `npm install -g newman`
- The Laravel server running (see below)

#### 1. Start the API server

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

#### 2. Clear rate-limit cache (important on repeated runs)

```bash
php artisan cache:clear
```

#### 3. Run Newman

```bash
newman run tests/postman-collection.json \
  --env-var "baseUrl=http://127.0.0.1:8000/api" \
  --delay-request 300 \
  --verbose \
  2>&1 | tee newman-results.txt
```

The collection handles **re-runs automatically** — if a plan, price, or user already exists, it falls back to fetching the existing record instead of failing.

---

### Copy results from server to your local machine

From your local machine (PowerShell on Windows):

```powershell
scp ubuntu@YOUR_SERVER_IP:/home/ubuntu/-Subscription_Lifecycle_Engine/newman-results.txt C:\zamil\newman-results.txt
```

Or on Linux/macOS:

```bash
scp ubuntu@YOUR_SERVER_IP:/home/ubuntu/-Subscription_Lifecycle_Engine/newman-results.txt ./newman-results.txt
```

Replace `YOUR_SERVER_IP` with your server's IP address.

---

## 2 — Manual Testing with VS Code REST Client

### Prerequisites

Start the server before running any tests:

```bash
PHP_CLI_SERVER_WORKERS=4 php artisan serve
```

Server runs at: `http://localhost:8000/api`

---

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

Run requests in this order — each one depends on the previous:

```
[1]  Register user
[3]  Login admin         ← copy adminToken
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

To verify that AED pricing is selected for UAE users, add this header to the subscription request:

```http
X-Forwarded-For: 94.200.1.1
```

This IP falls within the UAE range — the controller detects it and automatically selects AED.

---

## Common Issues

| Problem | Cause | Fix |
|---|---|---|
| `401 Unauthenticated` | Token is wrong or empty | Login again and update the token |
| `403 Forbidden` | Using userToken on an admin endpoint | Use `adminToken` instead |
| `409 Conflict` | Subscription already exists | Cancel or Expire it first |
| `422` on create transaction | Subscription is not in pending/trialing/past_due | Check the subscription status |
| Server not responding | Server is stopped | Run `PHP_CLI_SERVER_WORKERS=4 php artisan serve` |

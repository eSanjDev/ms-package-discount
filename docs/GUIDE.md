# 📚 Esanj Discount Client — Complete Guide

A step-by-step guide to creating and applying **coupons (discount codes)** and
**gift cards** through the Esanj Discount microservice from any Laravel application.

## Table of Contents

1. [What this package does](#1-what-this-package-does)
2. [How it works (the big picture)](#2-how-it-works-the-big-picture)
3. [Installation](#3-installation)
4. [Configuration & `.env`](#4-configuration--env)
5. [Three ways to call the client](#5-three-ways-to-call-the-client)
6. [Coupons: the usage lifecycle](#6-coupons-the-usage-lifecycle)
7. [Gift cards: the usage lifecycle](#7-gift-cards-the-usage-lifecycle)
8. [Creating coupons & gift cards](#8-creating-coupons--gift-cards)
9. [Handling errors safely](#9-handling-errors-safely)
10. [Configuration reference](#10-configuration-reference)
11. [API endpoints used](#11-api-endpoints-used)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. What this package does

It is a thin, typed HTTP client for the Discount microservice. You call PHP methods with
DTOs; it authenticates, sends the request, and hands you back typed response objects instead
of raw arrays. You never build URLs, attach bearer tokens, or `json_decode` responses yourself.

The service has two resources, exposed as two sub-clients:

- **Coupons** — percentage or fixed discount codes, applied to an order in a
  `validate → redeem → confirm | cancel` flow.
- **Gift cards** — fixed-value cards, applied to a user in a `validate → redeem` flow.

## 2. How it works (the big picture)

```
Your app ──CouponValidationData──▶ Coupon / GiftCard client
                                      │
                     ┌────────────────┴───────────────────┐
                     ▼                                     ▼
             AuthBridgeTokenProvider                    ApiClient (Guzzle)
             (esanj/auth-bridge)                        + Authorization: Bearer <jwt>
                     │                                     │
    client_credentials grant                     POST /api/v1/service/...
                     ▼                                     ▼
        OAuth server (ACCOUNTING_BRIDGE_BASE_URL)     Discount microservice
        issues a signed JWT  ───────────────────▶  validates the JWT (RS256) and responds
```

1. **Token** — `AuthBridgeTokenProvider` asks `esanj/auth-bridge` for an access token using the
   `client_credentials` grant with your `DISCOUNT_CLIENT_ID` / `DISCOUNT_CLIENT_SECRET`.
2. **Call** — `ApiClient` sends the request to the Discount service with that token as a
   `Bearer` header. The service validates the JWT and derives your service identity from it (you
   never send a service id yourself).
3. **Response** — the raw JSON is mapped into a typed Resource object.

### The access token is short-lived — and renewed for you

The access token issued by the `client_credentials` grant is deliberately short-lived. You do
**not** manage its lifecycle by hand:

- `esanj/auth-bridge` **caches** the token keyed by client id + scope, with a safety buffer
  (it treats the token as expired ~60s early). While it is valid, every call reuses it — you are
  not issuing a token on each request.
- When the cached token **expires**, the next call transparently obtains a fresh one and re-caches
  it. For the `client_credentials` grant this renewal is a new token request with your
  credentials (the standard machine-to-machine equivalent of "refresh").
- If the Discount service ever **rejects** a token (HTTP 401/403), `ApiClient` invalidates the
  cached token and retries the request with a freshly minted one (see the `retry` config).

The upshot: you pass a client id and secret once in config, and the client always sends a valid,
non-expired token.

## 3. Installation

In the root `composer.json`:

```jsonc
"require": {
    "esanj/discount-client": "dev-main"
},
"repositories": [
    { "type": "path", "url": "packages/esanj/ms-package-discount", "options": { "symlink": true } }
]
```

```bash
composer update esanj/discount-client
```

`esanj/auth-bridge` is a dependency and is already installed in this project.

## 4. Configuration & `.env`

```dotenv
DISCOUNT_SERVICE_URL=https://discount.esanj.io
DISCOUNT_CLIENT_ID=your-service-client-id
DISCOUNT_CLIENT_SECRET=your-service-client-secret

# Optional
DISCOUNT_SCOPE=*
DISCOUNT_TIMEOUT=30
DISCOUNT_RETRY_ATTEMPTS=3
DISCOUNT_RETRY_SLEEP_MS=1000
DISCOUNT_LOG_CHANNEL=

# auth-bridge (the OAuth server that issues the token)
ACCOUNTING_BRIDGE_BASE_URL=https://accounting.esanj.io
```

Publish the config file if you want to edit defaults directly:

```bash
php artisan vendor:publish --tag=discount-config   # → config/esanj/discount.php
```

## 5. Three ways to call the client

**Aggregated injection** — one client, both resources:

```php
use Esanj\DiscountClient\Contracts\DiscountClientInterface;

public function __construct(private DiscountClientInterface $discount) {}

$this->discount->coupons()->validate(/* … */);
$this->discount->giftCards()->validate(/* … */);
```

**Focused injection** — just the resource you need:

```php
use Esanj\DiscountClient\Contracts\CouponClientInterface;
use Esanj\DiscountClient\Contracts\GiftCardClientInterface;

public function __construct(
    private CouponClientInterface $coupons,
    private GiftCardClientInterface $giftCards,
) {}
```

**Facades:**

```php
use Esanj\DiscountClient\Facades\Coupon;
use Esanj\DiscountClient\Facades\GiftCard;
use Esanj\DiscountClient\Facades\Discount;   // Discount::coupons(), Discount::giftCards()
```

All resolve the same singletons.

## 6. Coupons: the usage lifecycle

A coupon is applied to an order in up to four steps. Each step is keyed by a **`usageId`** you
get back from `validate` — hold on to it.

```
validate ──▶ redeem ──▶ confirm   (order paid → coupon committed, used_count++)
   │            │
   │            └──▶ cancel        (order abandoned → reservation released)
   └── (computes the discount and reserves a usage; nothing is committed yet)
```

```php
use Esanj\DiscountClient\Facades\Coupon;
use Esanj\DiscountClient\DTOs\CouponValidationData;

$order = new CouponValidationData(
    userId: 42,
    amount: 1_000_000,   // order total, in the smallest currency unit
    currency: 'IRR',
    productId: 193,      // optional; required when the coupon is product-restricted
);

// 1) Validate — check the coupon against this order and reserve a usage.
$usage = Coupon::validate('WELCOME', $order);   // CouponUsageResource
$usage->usageId;          // e.g. '9f8b…'  (needed by every later step)
$usage->originalAmount;   // 1000000
$usage->discountAmount;   // 150000
$usage->finalAmount;      // 850000
$usage->currency;         // 'IRR'

// 2) Redeem — hold the coupon for this user while the order is being paid.
Coupon::redeem('WELCOME', $usage->usageId, $order);

// 3a) Confirm — the order was paid; commit the coupon (increments used_count).
Coupon::confirm('WELCOME', $usage->usageId);     // ActionResult { message, successCode }

// 3b) Cancel — the order was abandoned; release the reservation.
Coupon::cancel('WELCOME', $usage->usageId);
```

> **Reservations expire.** A redeemed-but-unconfirmed usage is held for a limited window on the
> service side (10 minutes) and then released automatically. Always `confirm` after a successful
> payment, or `cancel` if the flow is abandoned.

## 7. Gift cards: the usage lifecycle

Gift cards are simpler — `validate` then `redeem`, keyed by a `usageId`.

```php
use Esanj\DiscountClient\Facades\GiftCard;

// 1) Validate — check the card for this user and record a usage.
$usage = GiftCard::validate('GC-8H2K', userId: 42);   // GiftCardUsageResource
$usage->usageId;
$usage->amount;     // the card's value
$usage->currency;

// 2) Redeem — apply the card (increments its used_count).
GiftCard::redeem('GC-8H2K', $usage->usageId, userId: 42);
```

## 8. Creating coupons & gift cards

```php
use Esanj\DiscountClient\Facades\Coupon;
use Esanj\DiscountClient\Facades\GiftCard;
use Esanj\DiscountClient\DTOs\CouponData;
use Esanj\DiscountClient\DTOs\CouponProductData;
use Esanj\DiscountClient\DTOs\GiftCardData;
use Esanj\DiscountClient\Enums\CouponAmountType;

$coupon = Coupon::create(new CouponData(
    name: 'Welcome 15%',
    amountType: CouponAmountType::Percentage,   // or CouponAmountType::Fixed
    amount: 15,                                  // 15% (percentage) — or a fixed amount
    currency: ['IRR', 'IRT'],                    // one or more currencies
    minAmount: 500_000,                          // order must be at least this much
    maxAmount: 200_000,                          // cap the discount (≤ 100 for percentage coupons)
    usageLimit: 1000,                            // total redemptions allowed
    usageLimitPerUser: 1,
    tags: ['welcome'],                           // existing tag keys
    users: [42, 43],                             // restrict to these users (optional)
    services: [3],                               // restrict to these services (optional)
    products: [new CouponProductData(serviceId: 3, productId: 193)],
    isActive: true,
    startedAt: '2026-01-01 00:00:00',
    expiredAt: '2026-12-31 23:59:59',
));
$coupon->code;   // pass your own `code:` or let the service generate one

$giftCard = GiftCard::create(new GiftCardData(
    name: 'Yalda 500k',
    amount: 500_000,
    currency: 'IRR',            // a single currency (unlike coupons)
    usageLimit: 1,
    expiredAt: '2026-12-31 23:59:59',
));
```

`started_at` / `expired_at` accept an ISO date string or any `DateTimeInterface` (e.g. a Carbon
instance) — they are normalized to `Y-m-d H:i:s`.

**Server-side validation to keep in mind:**

- Coupon `name` and (when supplied) `code` must be unique; `code` may only contain `A-Z a-z 0-9 - _`.
- Coupon `amount_type` is `percentage` or `fixed`; for `percentage`, `max_amount` must be ≤ 100.
- Coupon `currency` is an **array**; gift card `currency` is a **single string** (≤ 3 chars).
- `tags` on a coupon must reference existing tag keys; `services.*` must be existing service ids.

## 9. Handling errors safely

```php
use Esanj\DiscountClient\Exceptions\DiscountApiException;
use Esanj\DiscountClient\Exceptions\DiscountAuthenticationException;
use Esanj\DiscountClient\Exceptions\DiscountException;

try {
    $usage = Coupon::validate('WELCOME', $order);
} catch (DiscountApiException $e) {
    if ($e->isBusinessRuleFailure()) {           // 400 — a coupon/gift-card rule rejected the request
        $reason = $e->getErrorCode();            // e.g. 'COUPON_EXPIRED'
        $human  = $e->getMessage();              // localized message from the service
    } elseif ($e->isValidationError()) {         // 422 — malformed payload
        $errors = $e->getErrors();               // ['amount' => ['...'], ...]
    } elseif ($e->isNotFound()) {                // 404 — unknown code / usage id
        // …
    } elseif ($e->isTooManyRequests()) {         // 429 — throttled (60 req/min)
        // …
    } else {
        report($e);                              // $e->statusCode, $e->responseBody
    }
} catch (DiscountAuthenticationException $e) {
    // Missing/invalid credentials, or the OAuth server rejected the token request.
    report($e);
}
```

- `DiscountException` is the base class — catch it to handle everything at once.
- Transient failures (HTTP 5xx, connection errors, and rejected tokens) are **retried
  automatically** before an exception is thrown. On a rejected token the client invalidates the
  cached token and fetches a fresh one on the next attempt.

### Business error codes (HTTP 400)

The `validate` / `redeem` calls surface why a code can't be applied via `getErrorCode()`:

| Coupon | Gift card |
| --- | --- |
| `COUPON_NOT_STARTED` | `GIFTCARD_NOT_STARTED` |
| `COUPON_EXPIRED` | `GIFTCARD_EXPIRED` |
| `COUPON_IS_INACTIVE` | `GIFTCARD_IS_INACTIVE` |
| `COUPON_MAX_USAGE_REACHED` | `GIFTCARD_MAX_USAGE_REACHED` |
| `COUPON_USAGE_LIMIT_REACHED` | `COUPON_USAGE_LIMIT_REACHED` |
| `COUPON_NOT_VALID_FOR_THIS_SERVICE` | `GIFTCARD_NOT_VALID_FOR_THIS_SERVICE` |
| `COUPON_NOT_VALID_FOR_THIS_PRODUCT` | `USER_NOT_ELIGIBLE_FOR_GIFTCARD` |
| `MINIMUM_ORDER_AMOUNT_NOT_MET` | |
| `MAXIMUM_ORDER_AMOUNT_EXCEEDED` | |
| `USER_NOT_ELIGIBLE_FOR_COUPON` | |

## 10. Configuration reference

| Key | Env | Default | Meaning |
| --- | --- | --- | --- |
| `base_url` | `DISCOUNT_SERVICE_URL` | `http://localhost` | Discount service base URL. |
| `client_id` | `DISCOUNT_CLIENT_ID` | — | Service OAuth client id. |
| `client_secret` | `DISCOUNT_CLIENT_SECRET` | — | Service OAuth client secret. |
| `scope` | `DISCOUNT_SCOPE` | `*` | OAuth scope requested. |
| `timeout` | `DISCOUNT_TIMEOUT` | `30` | HTTP timeout (seconds). |
| `retry.attempts` | `DISCOUNT_RETRY_ATTEMPTS` | `3` | Total attempts (1 = no retry). |
| `retry.sleep_ms` | `DISCOUNT_RETRY_SLEEP_MS` | `1000` | Delay between retries (ms). |
| `logging.channel` | `DISCOUNT_LOG_CHANNEL` | app default | Log channel for the client. |

The OAuth server URL itself comes from the auth-bridge config (`ACCOUNTING_BRIDGE_BASE_URL`).

## 11. API endpoints used

| Client method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `coupons()->create()` | POST | `/api/v1/service/coupons` | coupon payload |
| `coupons()->show()` | GET | `/api/v1/service/coupons/{code}` | — |
| `coupons()->validate()` | POST | `/api/v1/service/coupons/{code}/validate` | `{ user_id, amount, currency, product_id? }` |
| `coupons()->redeem()` | POST | `/api/v1/service/coupons/{code}/redeem` | `{ usage_id, user_id, amount, currency, product_id? }` |
| `coupons()->confirm()` | POST | `/api/v1/service/coupons/{code}/confirm` | `{ usage_id }` |
| `coupons()->cancel()` | POST | `/api/v1/service/coupons/{code}/cancel` | `{ usage_id }` |
| `giftCards()->create()` | POST | `/api/v1/service/gift-cards` | gift card payload |
| `giftCards()->show()` | GET | `/api/v1/service/gift-cards/{code}` | — |
| `giftCards()->validate()` | POST | `/api/v1/service/gift-cards/{code}/validate` | `{ user_id }` |
| `giftCards()->redeem()` | POST | `/api/v1/service/gift-cards/{code}/redeem` | `{ user_id, usage_id }` |

All endpoints require a valid `Bearer` token and are throttled to 60 requests/minute per service.

## 12. Troubleshooting

| Symptom | Likely cause |
| --- | --- |
| `DiscountAuthenticationException: credentials are not configured` | `DISCOUNT_CLIENT_ID` / `DISCOUNT_CLIENT_SECRET` not set. |
| `DiscountAuthenticationException` on every call | Wrong credentials, or `ACCOUNTING_BRIDGE_BASE_URL` points to the wrong OAuth server. |
| `DiscountApiException` with 401 after retries | The JWT is rejected by the Discount service (clock skew, wrong signing key, wrong audience). |
| `isBusinessRuleFailure()` (400) | Expected — inspect `getErrorCode()` (e.g. `COUPON_EXPIRED`). |
| `isValidationError()` (422) | Inspect `getErrors()` — usually `amount`, `currency`, `code`, or `amount_type`. |
| `isNotFound()` (404) on redeem/confirm | The `usageId` doesn't match a usage in the expected state (e.g. already confirmed/expired). |
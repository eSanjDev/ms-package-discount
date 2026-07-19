# Esanj Discount Client

A Laravel client package for the **Esanj Discount Microservice** (coupons & gift cards). It authenticates with client credentials through the [`esanj/auth-bridge`](../ms-package-accounting-bridge) package, then lets you create and apply **discount codes (coupons)** and **gift cards** — with typed DTOs, typed responses and structured error handling.

## Installation

The package lives in this monorepo and is wired up as a path repository. Add it to the root `composer.json`:

```jsonc
"require": {
    "esanj/discount-client": "dev-main"
},
"repositories": [
    { "type": "path", "url": "packages/esanj/ms-package-discount", "options": { "symlink": true } }
]
```

Then:

```bash
composer update esanj/discount-client
```

The service provider and the `Discount`, `Coupon` and `GiftCard` facades are auto-discovered.

## Configuration

Publish the config (optional — it is merged automatically):

```bash
php artisan vendor:publish --tag=discount-config
```

Set the environment variables:

```dotenv
# Discount microservice
DISCOUNT_SERVICE_URL=https://discount.esanj.io
DISCOUNT_CLIENT_ID=your-service-client-id
DISCOUNT_CLIENT_SECRET=your-service-client-secret

# Optional
DISCOUNT_SCOPE=*
DISCOUNT_TIMEOUT=30
DISCOUNT_RETRY_ATTEMPTS=3
DISCOUNT_RETRY_SLEEP_MS=1000
DISCOUNT_LOG_CHANNEL=

# Required by esanj/auth-bridge — the OAuth server that issues the token
ACCOUNTING_BRIDGE_BASE_URL=https://accounting.esanj.io
```

> The access token is issued by the OAuth server configured for `esanj/auth-bridge`
> (`ACCOUNTING_BRIDGE_BASE_URL`) using this service's `DISCOUNT_CLIENT_ID` / `DISCOUNT_CLIENT_SECRET`,
> then sent as a short-lived `Bearer` token to the Discount service. Tokens are cached and refreshed
> automatically by auth-bridge.

## Usage

### Dependency injection (recommended)

```php
use Esanj\DiscountClient\Contracts\DiscountClientInterface;

class CheckoutController
{
    public function __construct(private DiscountClientInterface $discount) {}

    public function apply()
    {
        $usage = $this->discount->coupons()->validate('WELCOME', new CouponValidationData(
            userId: 42, amount: 1_000_000, currency: 'IRR',
        ));
    }
}
```

You can also inject `CouponClientInterface` / `GiftCardClientInterface` directly.

### Facades

```php
use Esanj\DiscountClient\Facades\Coupon;
use Esanj\DiscountClient\Facades\GiftCard;
use Esanj\DiscountClient\Facades\Discount;   // Discount::coupons() / Discount::giftCards()
```

### Applying a coupon (validate → redeem → confirm / cancel)

```php
use Esanj\DiscountClient\Facades\Coupon;
use Esanj\DiscountClient\DTOs\CouponValidationData;

$order = new CouponValidationData(userId: 42, amount: 1_000_000, currency: 'IRR', productId: 193);

// 1) Validate against the order — computes the discount and reserves a usage.
$usage = Coupon::validate('WELCOME', $order);
$usage->usageId;          // keep this for the next steps
$usage->discountAmount;   // e.g. 150000
$usage->finalAmount;      // e.g. 850000

// 2) Redeem — holds the coupon for this user while payment is in progress.
Coupon::redeem('WELCOME', $usage->usageId, $order);

// 3a) Confirm once the order is paid (commits the coupon)…
Coupon::confirm('WELCOME', $usage->usageId);

// 3b) …or cancel to release the reservation.
Coupon::cancel('WELCOME', $usage->usageId);
```

### Applying a gift card (validate → redeem)

```php
use Esanj\DiscountClient\Facades\GiftCard;

$usage = GiftCard::validate('GC-8H2K', userId: 42);
$usage->amount;    // gift card balance/value
$usage->currency;

GiftCard::redeem('GC-8H2K', $usage->usageId, userId: 42);
```

### Creating coupons and gift cards

```php
use Esanj\DiscountClient\Facades\Coupon;
use Esanj\DiscountClient\Facades\GiftCard;
use Esanj\DiscountClient\DTOs\CouponData;
use Esanj\DiscountClient\DTOs\CouponProductData;
use Esanj\DiscountClient\DTOs\GiftCardData;
use Esanj\DiscountClient\Enums\CouponAmountType;

$coupon = Coupon::create(new CouponData(
    name: 'Welcome 15%',
    amountType: CouponAmountType::Percentage,
    amount: 15,                       // 15% off…
    currency: ['IRR'],
    maxAmount: 200_000,               // …capped at 200,000
    usageLimit: 1000,
    usageLimitPerUser: 1,
    products: [new CouponProductData(serviceId: 3, productId: 193)],
    expiredAt: '2026-12-31 23:59:59',
));
$coupon->code;                        // generated when you don't pass one

$giftCard = GiftCard::create(new GiftCardData(
    name: 'Yalda 500k',
    amount: 500_000,
    currency: 'IRR',
    usageLimit: 1,
));
```

## Error Handling

Every failure throws a subclass of `Esanj\DiscountClient\Exceptions\DiscountException`:

| Exception | When |
| --- | --- |
| `DiscountAuthenticationException` | Client credentials missing/invalid; the OAuth server rejected the token request. |
| `DiscountApiException` | The Discount service returned an error response. Carries `statusCode`, `responseBody`, `getErrorCode()`, `getErrors()`. |

```php
use Esanj\DiscountClient\Exceptions\DiscountApiException;
use Esanj\DiscountClient\Exceptions\DiscountAuthenticationException;

try {
    $usage = Coupon::validate('WELCOME', $order);
} catch (DiscountApiException $e) {
    if ($e->isBusinessRuleFailure()) {           // 400 — a coupon/gift-card rule rejected it
        match ($e->getErrorCode()) {
            'COUPON_EXPIRED'             => /* … */,
            'COUPON_MAX_USAGE_REACHED'   => /* … */,
            'MINIMUM_ORDER_AMOUNT_NOT_MET' => /* … */,
            default                      => report($e),
        };
    } elseif ($e->isNotFound())        { /* unknown code */ }
    elseif ($e->isValidationError())   { $errors = $e->getErrors(); }   // 422
    else                               { report($e); }
} catch (DiscountAuthenticationException $e) {
    // credentials / OAuth problem
}
```

Server (5xx), connection and rejected-token (401/403) failures are retried automatically
according to the `retry` config; a rejected token is invalidated and re-fetched before the retry.

## API surface

| Method | HTTP | Endpoint |
| --- | --- | --- |
| `coupons()->create(CouponData)` | POST | `/api/v1/service/coupons` |
| `coupons()->show(string $code)` | GET | `/api/v1/service/coupons/{code}` |
| `coupons()->validate(string $code, CouponValidationData)` | POST | `/api/v1/service/coupons/{code}/validate` |
| `coupons()->redeem(string $code, string $usageId, CouponValidationData)` | POST | `/api/v1/service/coupons/{code}/redeem` |
| `coupons()->confirm(string $code, string $usageId)` | POST | `/api/v1/service/coupons/{code}/confirm` |
| `coupons()->cancel(string $code, string $usageId)` | POST | `/api/v1/service/coupons/{code}/cancel` |
| `giftCards()->create(GiftCardData)` | POST | `/api/v1/service/gift-cards` |
| `giftCards()->show(string $code)` | GET | `/api/v1/service/gift-cards/{code}` |
| `giftCards()->validate(string $code, int $userId)` | POST | `/api/v1/service/gift-cards/{code}/validate` |
| `giftCards()->redeem(string $code, string $usageId, int $userId)` | POST | `/api/v1/service/gift-cards/{code}/redeem` |

See [`docs/GUIDE.md`](docs/GUIDE.md) for a full walkthrough.

## License

MIT
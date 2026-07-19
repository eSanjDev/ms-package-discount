<?php

namespace Esanj\DiscountClient\DTOs;

use DateTimeInterface;
use Esanj\DiscountClient\Enums\CouponAmountType;

/**
 * Payload for POST /api/v1/service/coupons (create a coupon).
 *
 * When $code is null the service generates one. $currency is a list of the
 * currency codes the coupon is valid for. For a percentage coupon, $amount is
 * the percentage and $maxAmount caps the resulting discount.
 */
final class CouponData
{
    /**
     * @param string[]             $currency  Currency codes the coupon applies to, e.g. ['IRR', 'IRT'].
     * @param string[]             $tags      Existing tag keys.
     * @param int[]                $users     User ids the coupon is limited to.
     * @param int[]                $services  Service ids the coupon is limited to.
     * @param CouponProductData[]  $products  Products the coupon is limited to.
     */
    public function __construct(
        public readonly string $name,
        public readonly CouponAmountType $amountType,
        public readonly int $amount,
        public readonly array $currency,
        public readonly ?string $code = null,
        public readonly ?int $minAmount = null,
        public readonly ?int $maxAmount = null,
        public readonly ?int $usageLimit = null,
        public readonly ?int $usageLimitPerUser = null,
        public readonly array $tags = [],
        public readonly ?string $description = null,
        public readonly array $users = [],
        public readonly array $services = [],
        public readonly array $products = [],
        public readonly ?bool $isActive = null,
        public readonly DateTimeInterface|string|null $startedAt = null,
        public readonly DateTimeInterface|string|null $expiredAt = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name'                 => $this->name,
            'code'                 => $this->code,
            'amount_type'          => $this->amountType->value,
            'amount'               => $this->amount,
            'min_amount'           => $this->minAmount,
            'max_amount'           => $this->maxAmount,
            'currency'             => $this->currency,
            'usage_limit'          => $this->usageLimit,
            'usage_limit_per_user' => $this->usageLimitPerUser,
            'tags'                 => $this->tags ?: null,
            'description'          => $this->description,
            'users'                => $this->users ?: null,
            'services'             => $this->services ?: null,
            'products'             => $this->products
                ? array_map(fn (CouponProductData $product) => $product->toArray(), $this->products)
                : null,
            'is_active'            => $this->isActive,
            'started_at'           => $this->formatDate($this->startedAt),
            'expired_at'           => $this->formatDate($this->expiredAt),
        ], fn ($value) => $value !== null);
    }

    private function formatDate(DateTimeInterface|string|null $date): ?string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d H:i:s') : $date;
    }
}
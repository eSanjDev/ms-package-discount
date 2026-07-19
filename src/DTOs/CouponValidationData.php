<?php

namespace Esanj\DiscountClient\DTOs;

/**
 * Payload for validating and redeeming a coupon against a specific order.
 *
 * Shared by POST /coupons/{code}/validate and POST /coupons/{code}/redeem.
 */
final class CouponValidationData
{
    public function __construct(
        public readonly int $userId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?int $productId = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'user_id'    => $this->userId,
            'amount'     => $this->amount,
            'currency'   => $this->currency,
            'product_id' => $this->productId,
        ], fn ($value) => $value !== null);
    }
}
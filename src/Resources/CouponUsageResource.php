<?php

namespace Esanj\DiscountClient\Resources;

/**
 * The result of validating or redeeming a coupon: the usage record plus the
 * computed discount for the order. Keep {@see $usageId} — the redeem, confirm
 * and cancel calls need it.
 */
final class CouponUsageResource
{
    public function __construct(
        public readonly string $usageId,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly int|float $originalAmount,
        public readonly int|float $discountAmount,
        public readonly int|float $finalAmount,
        public readonly string $currency,
    ) {}

    public static function fromArray(array $response): self
    {
        $item = $response['data'] ?? $response;

        return new self(
            usageId:        $item['usage_id'] ?? '',
            name:           $item['name'] ?? null,
            description:    $item['description'] ?? null,
            originalAmount: $item['original_amount'] ?? 0,
            discountAmount: $item['discount_amount'] ?? 0,
            finalAmount:    $item['final_amount'] ?? 0,
            currency:       $item['currency'] ?? '',
        );
    }
}
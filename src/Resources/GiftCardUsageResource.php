<?php

namespace Esanj\DiscountClient\Resources;

/**
 * The result of validating or redeeming a gift card. Keep {@see $usageId} —
 * the redeem call needs it.
 */
final class GiftCardUsageResource
{
    public function __construct(
        public readonly string $usageId,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly int|float $amount,
        public readonly string $currency,
    ) {}

    public static function fromArray(array $response): self
    {
        $item = $response['data'] ?? $response;

        return new self(
            usageId:     $item['usage_id'] ?? '',
            name:        $item['name'] ?? null,
            description: $item['description'] ?? null,
            amount:      $item['amount'] ?? 0,
            currency:    $item['currency'] ?? '',
        );
    }
}
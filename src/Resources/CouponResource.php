<?php

namespace Esanj\DiscountClient\Resources;

use Esanj\DiscountClient\Enums\CouponAmountType;

/**
 * A coupon returned by the create / show endpoints.
 */
final class CouponResource
{
    /**
     * @param string[]     $tags
     * @param array<mixed> $users
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly ?CouponAmountType $amountType,
        public readonly int $amount,
        public readonly ?int $minAmount,
        public readonly ?int $maxAmount,
        public readonly ?int $usageLimit,
        public readonly ?int $usageLimitPerUser,
        public readonly int $usedCount,
        public readonly ?string $description,
        public readonly array $tags,
        public readonly bool $isActive,
        public readonly ?string $creatorType,
        public readonly array $users,
        public readonly ?string $startedAt,
        public readonly ?string $expiredAt,
        public readonly ?string $deletedAt,
        public readonly ?string $updatedAt,
        public readonly ?string $createdAt,
    ) {}

    public static function fromArray(array $response): self
    {
        $item = $response['data'] ?? $response;

        return new self(
            id:                $item['id'] ?? 0,
            name:              $item['name'] ?? '',
            code:              $item['code'] ?? '',
            amountType:        isset($item['amount_type']) ? CouponAmountType::tryFrom((string) $item['amount_type']) : null,
            amount:            (int) ($item['amount'] ?? 0),
            minAmount:         $item['min_amount'] ?? null,
            maxAmount:         $item['max_amount'] ?? null,
            usageLimit:        $item['usage_limit'] ?? null,
            usageLimitPerUser: $item['usage_limit_per_user'] ?? null,
            usedCount:         (int) ($item['used_count'] ?? 0),
            description:       $item['description'] ?? null,
            tags:              $item['tags'] ?? [],
            isActive:          (bool) ($item['is_active'] ?? false),
            creatorType:       $item['creator_type'] ?? null,
            users:             $item['users'] ?? [],
            startedAt:         $item['started_at'] ?? null,
            expiredAt:         $item['expired_at'] ?? null,
            deletedAt:         $item['deleted_at'] ?? null,
            updatedAt:         $item['updated_at'] ?? null,
            createdAt:         $item['created_at'] ?? null,
        );
    }
}
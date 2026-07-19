<?php

namespace Esanj\DiscountClient\Resources;

/**
 * A gift card returned by the create / show endpoints.
 */
final class GiftCardResource
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly array $tags,
        public readonly ?string $description,
        public readonly int|float $amount,
        public readonly string $currency,
        public readonly int $usedCount,
        public readonly ?int $usageLimit,
        public readonly ?int $usageLimitPerUser,
        public readonly bool $isActive,
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
            tags:              $item['tags'] ?? [],
            description:       $item['description'] ?? null,
            amount:            $item['amount'] ?? 0,
            currency:          $item['currency'] ?? '',
            usedCount:         (int) ($item['used_count'] ?? 0),
            usageLimit:        $item['usage_limit'] ?? null,
            usageLimitPerUser: $item['usage_limit_per_user'] ?? null,
            isActive:          (bool) ($item['is_active'] ?? false),
            startedAt:         $item['started_at'] ?? null,
            expiredAt:         $item['expired_at'] ?? null,
            deletedAt:         $item['deleted_at'] ?? null,
            updatedAt:         $item['updated_at'] ?? null,
            createdAt:         $item['created_at'] ?? null,
        );
    }
}
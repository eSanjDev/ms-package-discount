<?php

namespace Esanj\DiscountClient\DTOs;

use DateTimeInterface;

/**
 * Payload for POST /api/v1/service/gift-cards (create a gift card).
 *
 * When $code is null the service generates one. Unlike a coupon, a gift card
 * has a single $currency and a fixed $amount.
 */
final class GiftCardData
{
    /**
     * @param string[] $tags     Free-form tags.
     * @param int[]    $services Service ids the gift card is limited to.
     * @param int[]    $users    User ids the gift card is limited to.
     */
    public function __construct(
        public readonly string $name,
        public readonly int|float $amount,
        public readonly string $currency,
        public readonly ?string $code = null,
        public readonly array $tags = [],
        public readonly ?string $description = null,
        public readonly ?int $usageLimit = null,
        public readonly ?int $usageLimitPerUser = null,
        public readonly array $services = [],
        public readonly array $users = [],
        public readonly ?bool $isActive = null,
        public readonly DateTimeInterface|string|null $startedAt = null,
        public readonly DateTimeInterface|string|null $expiredAt = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name'                 => $this->name,
            'code'                 => $this->code,
            'amount'               => $this->amount,
            'currency'             => $this->currency,
            'tags'                 => $this->tags ?: null,
            'description'          => $this->description,
            'usage_limit'          => $this->usageLimit,
            'usage_limit_per_user' => $this->usageLimitPerUser,
            'services'             => $this->services ?: null,
            'users'                => $this->users ?: null,
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
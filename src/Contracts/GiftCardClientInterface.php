<?php

namespace Esanj\DiscountClient\Contracts;

use Esanj\DiscountClient\DTOs\GiftCardData;
use Esanj\DiscountClient\Resources\GiftCardResource;
use Esanj\DiscountClient\Resources\GiftCardUsageResource;

interface GiftCardClientInterface
{
    /**
     * Create a new gift card.
     */
    public function create(GiftCardData $data): GiftCardResource;

    /**
     * Fetch a gift card by its code.
     */
    public function show(string $code): GiftCardResource;

    /**
     * Check a gift card for a user and record a usage. The returned resource's
     * usageId is required by redeem.
     */
    public function validate(string $code, int $userId): GiftCardUsageResource;

    /**
     * Redeem a previously validated usage (increments the gift card's used count).
     */
    public function redeem(string $code, string $usageId, int $userId): GiftCardUsageResource;
}
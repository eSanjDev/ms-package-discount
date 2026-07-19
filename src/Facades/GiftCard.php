<?php

namespace Esanj\DiscountClient\Facades;

use Esanj\DiscountClient\Contracts\GiftCardClientInterface;
use Esanj\DiscountClient\DTOs\GiftCardData;
use Esanj\DiscountClient\Resources\GiftCardResource;
use Esanj\DiscountClient\Resources\GiftCardUsageResource;
use Illuminate\Support\Facades\Facade;

/**
 * @method static GiftCardResource create(GiftCardData $data)
 * @method static GiftCardResource show(string $code)
 * @method static GiftCardUsageResource validate(string $code, int $userId)
 * @method static GiftCardUsageResource redeem(string $code, string $usageId, int $userId)
 *
 * @see \Esanj\DiscountClient\GiftCardClient
 */
class GiftCard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GiftCardClientInterface::class;
    }
}
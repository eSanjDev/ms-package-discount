<?php

namespace Esanj\DiscountClient\Facades;

use Esanj\DiscountClient\Contracts\CouponClientInterface;
use Esanj\DiscountClient\Contracts\DiscountClientInterface;
use Esanj\DiscountClient\Contracts\GiftCardClientInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CouponClientInterface coupons()
 * @method static GiftCardClientInterface giftCards()
 *
 * @see \Esanj\DiscountClient\DiscountClient
 */
class Discount extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DiscountClientInterface::class;
    }
}
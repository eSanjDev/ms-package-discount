<?php

namespace Esanj\DiscountClient;

use Esanj\DiscountClient\Contracts\CouponClientInterface;
use Esanj\DiscountClient\Contracts\DiscountClientInterface;
use Esanj\DiscountClient\Contracts\GiftCardClientInterface;

/**
 * Entry point that groups the Discount service's two resources behind a single
 * injectable client: {@see coupons()} and {@see giftCards()}.
 */
class DiscountClient implements DiscountClientInterface
{
    public function __construct(
        private readonly CouponClientInterface $coupons,
        private readonly GiftCardClientInterface $giftCards,
    ) {}

    public function coupons(): CouponClientInterface
    {
        return $this->coupons;
    }

    public function giftCards(): GiftCardClientInterface
    {
        return $this->giftCards;
    }
}
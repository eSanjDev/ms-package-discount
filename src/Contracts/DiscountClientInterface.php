<?php

namespace Esanj\DiscountClient\Contracts;

interface DiscountClientInterface
{
    /**
     * The coupon (discount code) endpoints of the Discount service.
     */
    public function coupons(): CouponClientInterface;

    /**
     * The gift card endpoints of the Discount service.
     */
    public function giftCards(): GiftCardClientInterface;
}
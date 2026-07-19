<?php

namespace Esanj\DiscountClient\Enums;

/**
 * How a coupon's {@see \Esanj\DiscountClient\DTOs\CouponData::$amount} is applied.
 */
enum CouponAmountType: string
{
    /** $amount is a percentage of the order (max_amount caps the discount). */
    case Percentage = 'percentage';

    /** $amount is a fixed discount in the order currency. */
    case Fixed = 'fixed';

    public function isPercentage(): bool
    {
        return $this === self::Percentage;
    }

    public function isFixed(): bool
    {
        return $this === self::Fixed;
    }
}
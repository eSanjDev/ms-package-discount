<?php

namespace Esanj\DiscountClient\Facades;

use Esanj\DiscountClient\Contracts\CouponClientInterface;
use Esanj\DiscountClient\DTOs\CouponData;
use Esanj\DiscountClient\DTOs\CouponValidationData;
use Esanj\DiscountClient\Resources\ActionResult;
use Esanj\DiscountClient\Resources\CouponResource;
use Esanj\DiscountClient\Resources\CouponUsageResource;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CouponResource create(CouponData $data)
 * @method static CouponResource show(string $code)
 * @method static CouponUsageResource validate(string $code, CouponValidationData $data)
 * @method static CouponUsageResource redeem(string $code, string $usageId, CouponValidationData $data)
 * @method static ActionResult confirm(string $code, string $usageId)
 * @method static ActionResult cancel(string $code, string $usageId)
 *
 * @see \Esanj\DiscountClient\CouponClient
 */
class Coupon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CouponClientInterface::class;
    }
}
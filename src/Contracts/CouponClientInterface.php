<?php

namespace Esanj\DiscountClient\Contracts;

use Esanj\DiscountClient\DTOs\CouponData;
use Esanj\DiscountClient\DTOs\CouponValidationData;
use Esanj\DiscountClient\Resources\ActionResult;
use Esanj\DiscountClient\Resources\CouponResource;
use Esanj\DiscountClient\Resources\CouponUsageResource;

interface CouponClientInterface
{
    /**
     * Create a new coupon.
     */
    public function create(CouponData $data): CouponResource;

    /**
     * Fetch a coupon by its code.
     */
    public function show(string $code): CouponResource;

    /**
     * Check a coupon against an order and reserve a usage record. The returned
     * resource's usageId is required by redeem / confirm / cancel.
     */
    public function validate(string $code, CouponValidationData $data): CouponUsageResource;

    /**
     * Redeem a previously validated usage, holding the coupon for the user
     * until it is confirmed or canceled.
     */
    public function redeem(string $code, string $usageId, CouponValidationData $data): CouponUsageResource;

    /**
     * Confirm a redeemed usage, committing the coupon (increments its used count).
     */
    public function confirm(string $code, string $usageId): ActionResult;

    /**
     * Cancel a redeemed usage, releasing the reservation.
     */
    public function cancel(string $code, string $usageId): ActionResult;
}
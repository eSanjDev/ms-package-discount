<?php

namespace Esanj\DiscountClient\DTOs;

/**
 * A single product a coupon is restricted to, identified within its owning
 * service. Used in {@see CouponData::$products}.
 */
final class CouponProductData
{
    public function __construct(
        public readonly int $serviceId,
        public readonly int $productId,
    ) {}

    public function toArray(): array
    {
        return [
            'service_id' => $this->serviceId,
            'id'         => $this->productId,
        ];
    }
}
<?php

namespace Esanj\DiscountClient\Resources;

/**
 * The confirmation returned by actions that don't carry a resource of their own
 * (coupon confirm / cancel): a human-readable message and a machine-readable
 * success code such as "coupon_confirmed".
 */
final class ActionResult
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $successCode,
        public readonly array $data = [],
    ) {}

    public static function fromArray(array $response): self
    {
        return new self(
            message:     $response['message'] ?? '',
            successCode: $response['success_code'] ?? null,
            data:        $response['data'] ?? [],
        );
    }
}
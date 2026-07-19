<?php

namespace Esanj\DiscountClient\Exceptions;

use Throwable;

/**
 * Thrown when the Discount service returns an error response.
 *
 * Business rule failures on validate/redeem (expired coupon, usage limit
 * reached, user not eligible, …) arrive as HTTP 400 with a machine-readable
 * {@see getErrorCode()} such as COUPON_EXPIRED or GIFTCARD_MAX_USAGE_REACHED.
 */
class DiscountApiException extends DiscountException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly array $responseBody = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * The service's machine-readable error code, e.g. "COUPON_EXPIRED".
     */
    public function getErrorCode(): ?string
    {
        return $this->responseBody['error_code'] ?? null;
    }

    /**
     * Field-level validation errors, when present (HTTP 422).
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->responseBody['errors'] ?? [];
    }

    public function isUnauthorized(): bool
    {
        return $this->statusCode === 401;
    }

    public function isForbidden(): bool
    {
        return $this->statusCode === 403;
    }

    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    public function isValidationError(): bool
    {
        return $this->statusCode === 422;
    }

    public function isTooManyRequests(): bool
    {
        return $this->statusCode === 429;
    }

    /**
     * A coupon/gift-card business rule rejected the request (e.g. expired,
     * inactive, usage limit reached). Inspect {@see getErrorCode()} for which.
     */
    public function isBusinessRuleFailure(): bool
    {
        return $this->statusCode === 400;
    }
}
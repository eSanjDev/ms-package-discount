<?php

namespace Esanj\DiscountClient\Contracts;

use Esanj\DiscountClient\Exceptions\DiscountAuthenticationException;

interface TokenProviderInterface
{
    /**
     * Return the "Bearer xxx" Authorization header for the Discount service.
     *
     * @throws DiscountAuthenticationException
     */
    public function authorizationHeader(): string;

    /**
     * Drop the currently cached token so the next call fetches a fresh one.
     */
    public function invalidate(): void;
}
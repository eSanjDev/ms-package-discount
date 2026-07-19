<?php

namespace Esanj\DiscountClient\Exceptions;

use Throwable;

class DiscountAuthenticationException extends DiscountException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
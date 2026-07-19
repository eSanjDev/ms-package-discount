<?php

namespace Esanj\DiscountClient\Auth;

use Esanj\AuthBridge\Contracts\ClientCredentialsServiceInterface;
use Esanj\AuthBridge\Exceptions\TokenRequestException;
use Esanj\DiscountClient\Contracts\TokenProviderInterface;
use Esanj\DiscountClient\Exceptions\DiscountAuthenticationException;

/**
 * Obtains a Discount-service access token through the auth-bridge package's
 * client-credentials grant. The access token is short-lived; auth-bridge caches
 * it (with an expiry buffer) and transparently requests a fresh one once it
 * expires, so this provider simply delegates to it. On a rejected token the
 * ApiClient calls {@see invalidate()} and the next call re-authenticates.
 */
class AuthBridgeTokenProvider implements TokenProviderInterface
{
    public function __construct(
        private readonly ClientCredentialsServiceInterface $credentials,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly ?string $scope = null,
    ) {}

    public function authorizationHeader(): string
    {
        $this->guardCredentials();

        try {
            return $this->credentials
                ->getAccessToken($this->clientId, $this->clientSecret, $this->scope)
                ->getAuthorizationHeader();
        } catch (TokenRequestException $e) {
            throw new DiscountAuthenticationException(
                'Could not obtain an access token for the discount service: ' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    public function invalidate(): void
    {
        if ($this->clientId !== '') {
            $this->credentials->invalidateToken($this->clientId, $this->scope);
        }
    }

    private function guardCredentials(): void
    {
        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new DiscountAuthenticationException(
                'Discount client credentials are not configured. Set DISCOUNT_CLIENT_ID and DISCOUNT_CLIENT_SECRET.'
            );
        }
    }
}
<?php

namespace Esanj\DiscountClient;

use Esanj\AuthBridge\Contracts\ClientCredentialsServiceInterface;
use Esanj\DiscountClient\Auth\AuthBridgeTokenProvider;
use Esanj\DiscountClient\Contracts\CouponClientInterface;
use Esanj\DiscountClient\Contracts\DiscountClientInterface;
use Esanj\DiscountClient\Contracts\GiftCardClientInterface;
use Esanj\DiscountClient\Contracts\TokenProviderInterface;
use Esanj\DiscountClient\Http\ApiClient;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class DiscountClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/discount.php', 'esanj.discount');

        $this->app->singleton(TokenProviderInterface::class, function ($app) {
            $config = $app['config']['esanj']['discount'];

            return new AuthBridgeTokenProvider(
                credentials:  $app->make(ClientCredentialsServiceInterface::class),
                clientId:     (string) $config['client_id'],
                clientSecret: (string) $config['client_secret'],
                scope:        $config['scope'] ?? null,
            );
        });

        $this->app->singleton(ApiClient::class, function ($app) {
            $config = $app['config']['esanj']['discount'];

            $logChannel = $config['logging']['channel'] ?? null;
            $logger = $logChannel
                ? $app['log']->channel($logChannel)
                : $app[LoggerInterface::class];

            return new ApiClient(
                httpClient:    new Client(['timeout' => $config['timeout'], 'connect_timeout' => 10]),
                tokenProvider: $app[TokenProviderInterface::class],
                logger:        $logger,
                baseUrl:       $config['base_url'],
                retryAttempts: max(1, (int) $config['retry']['attempts']),
                retrySleepMs:  (int) $config['retry']['sleep_ms'],
            );
        });

        $this->app->singleton(CouponClientInterface::class, fn ($app) => new CouponClient($app[ApiClient::class]));
        $this->app->singleton(GiftCardClientInterface::class, fn ($app) => new GiftCardClient($app[ApiClient::class]));

        $this->app->singleton(DiscountClientInterface::class, fn ($app) => new DiscountClient(
            $app[CouponClientInterface::class],
            $app[GiftCardClientInterface::class],
        ));

        $this->app->alias(CouponClientInterface::class, CouponClient::class);
        $this->app->alias(GiftCardClientInterface::class, GiftCardClient::class);
        $this->app->alias(DiscountClientInterface::class, DiscountClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/discount.php' => config_path('esanj/discount.php'),
            ], 'discount-config');
        }
    }
}
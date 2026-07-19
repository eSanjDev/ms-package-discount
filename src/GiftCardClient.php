<?php

namespace Esanj\DiscountClient;

use Esanj\DiscountClient\Contracts\GiftCardClientInterface;
use Esanj\DiscountClient\DTOs\GiftCardData;
use Esanj\DiscountClient\Http\ApiClient;
use Esanj\DiscountClient\Resources\GiftCardResource;
use Esanj\DiscountClient\Resources\GiftCardUsageResource;

class GiftCardClient implements GiftCardClientInterface
{
    private const BASE = 'api/v1/service/gift-cards';

    public function __construct(private readonly ApiClient $apiClient) {}

    public function create(GiftCardData $data): GiftCardResource
    {
        return GiftCardResource::fromArray($this->apiClient->post(self::BASE, $data->toArray()));
    }

    public function show(string $code): GiftCardResource
    {
        return GiftCardResource::fromArray($this->apiClient->get($this->path($code)));
    }

    public function validate(string $code, int $userId): GiftCardUsageResource
    {
        return GiftCardUsageResource::fromArray(
            $this->apiClient->post($this->path($code, 'validate'), ['user_id' => $userId])
        );
    }

    public function redeem(string $code, string $usageId, int $userId): GiftCardUsageResource
    {
        return GiftCardUsageResource::fromArray(
            $this->apiClient->post($this->path($code, 'redeem'), ['user_id' => $userId, 'usage_id' => $usageId])
        );
    }

    private function path(string $code, ?string $action = null): string
    {
        $path = self::BASE . '/' . rawurlencode($code);

        return $action ? "{$path}/{$action}" : $path;
    }
}
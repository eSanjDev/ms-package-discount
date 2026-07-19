<?php

namespace Esanj\DiscountClient;

use Esanj\DiscountClient\Contracts\CouponClientInterface;
use Esanj\DiscountClient\DTOs\CouponData;
use Esanj\DiscountClient\DTOs\CouponValidationData;
use Esanj\DiscountClient\Http\ApiClient;
use Esanj\DiscountClient\Resources\ActionResult;
use Esanj\DiscountClient\Resources\CouponResource;
use Esanj\DiscountClient\Resources\CouponUsageResource;

class CouponClient implements CouponClientInterface
{
    private const BASE = 'api/v1/service/coupons';

    public function __construct(private readonly ApiClient $apiClient) {}

    public function create(CouponData $data): CouponResource
    {
        return CouponResource::fromArray($this->apiClient->post(self::BASE, $data->toArray()));
    }

    public function show(string $code): CouponResource
    {
        return CouponResource::fromArray($this->apiClient->get($this->path($code)));
    }

    public function validate(string $code, CouponValidationData $data): CouponUsageResource
    {
        return CouponUsageResource::fromArray(
            $this->apiClient->post($this->path($code, 'validate'), $data->toArray())
        );
    }

    public function redeem(string $code, string $usageId, CouponValidationData $data): CouponUsageResource
    {
        return CouponUsageResource::fromArray(
            $this->apiClient->post(
                $this->path($code, 'redeem'),
                array_merge($data->toArray(), ['usage_id' => $usageId]),
            )
        );
    }

    public function confirm(string $code, string $usageId): ActionResult
    {
        return ActionResult::fromArray(
            $this->apiClient->post($this->path($code, 'confirm'), ['usage_id' => $usageId])
        );
    }

    public function cancel(string $code, string $usageId): ActionResult
    {
        return ActionResult::fromArray(
            $this->apiClient->post($this->path($code, 'cancel'), ['usage_id' => $usageId])
        );
    }

    private function path(string $code, ?string $action = null): string
    {
        $path = self::BASE . '/' . rawurlencode($code);

        return $action ? "{$path}/{$action}" : $path;
    }
}
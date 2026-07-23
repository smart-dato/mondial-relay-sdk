<?php

namespace SmartDato\MondialRelay;

use Illuminate\Support\Collection;
use SmartDato\MondialRelay\V1\Client;
use SmartDato\MondialRelay\V1\Data\PickupPoint;
use SmartDato\MondialRelay\V1\Data\TrackingResult;
use SmartDato\MondialRelay\V1\Queries\PickupPointSearchQuery;

class MondialRelay
{
    public function __construct(
        protected Client $client,
        protected string $defaultLanguage = 'FR',
    ) {}

    /** @return Collection<int, PickupPoint> */
    public function searchPickupPoints(PickupPointSearchQuery $query): Collection
    {
        return $this->client->searchPickupPoints($query);
    }

    public function pickupPoint(string $country, string $number): ?PickupPoint
    {
        $query = new PickupPointSearchQuery(
            country: $country,
            maxResults: 1,
            pickupPointNumber: $number,
        );

        return $this->client->searchPickupPoints($query)->first();
    }

    public function trackParcel(string $shipmentNumber, ?string $language = null): TrackingResult
    {
        return $this->client->trackParcel($shipmentNumber, $language ?? $this->defaultLanguage);
    }

    public function lastRawRequest(): ?string
    {
        return $this->client->lastRawRequest();
    }

    public function lastRawResponse(): ?string
    {
        return $this->client->lastRawResponse();
    }
}

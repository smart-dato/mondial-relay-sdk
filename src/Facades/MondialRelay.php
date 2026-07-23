<?php

namespace SmartDato\MondialRelay\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use SmartDato\MondialRelay\V1\Data\PickupPoint;
use SmartDato\MondialRelay\V1\Data\TrackingResult;
use SmartDato\MondialRelay\V1\Queries\PickupPointSearchQuery;

/**
 * @method static Collection<int, PickupPoint> searchPickupPoints(PickupPointSearchQuery $query)
 * @method static ?PickupPoint pickupPoint(string $country, string $number)
 * @method static TrackingResult trackParcel(string $shipmentNumber, ?string $language = null)
 *
 * @see \SmartDato\MondialRelay\MondialRelay
 */
class MondialRelay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SmartDato\MondialRelay\MondialRelay::class;
    }
}

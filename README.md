# Mondial Relay Shipping SDK for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/smart-dato/mondial-relay-sdk.svg?style=flat-square)](https://packagist.org/packages/smart-dato/mondial-relay-sdk)
[![GitHub Tests Action Status](https://github.com/smart-dato/mondial-relay-sdk/actions/workflows/run-tests.yml/badge.svg)](https://github.com/smart-dato/mondial-relay-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/smart-dato/mondial-relay-sdk/actions/workflows/code-style.yml/badge.svg)](https://github.com/smart-dato/mondial-relay-sdk/actions?query=workflow%3A%22Code+style%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/smart-dato/mondial-relay-sdk.svg?style=flat-square)](https://packagist.org/packages/smart-dato/mondial-relay-sdk)

A Laravel SDK for the [Mondial Relay](https://www.mondialrelay.fr) shipping APIs.

Currently implemented — **API V1 (SOAP)**:

- Pickup point search (`WSI4_PointRelais_Recherche`)
- Parcel tracking (`WSI2_TracingColisDetaille`)

Planned — **API V2 (Dual Carrier)**: shipment and label creation.

## Installation

Install the package via composer:

```bash
composer require smart-dato/mondial-relay-sdk
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="mondial-relay-sdk-config"
```

Configure your credentials in `.env`:

```dotenv
MONDIAL_RELAY_ENSEIGNE=M1ITTEST
MONDIAL_RELAY_PRIVATE_KEY=your-private-key
```

## Usage

### Search pickup points

```php
use SmartDato\MondialRelay\Facades\MondialRelay;
use SmartDato\MondialRelay\V1\Queries\PickupPointSearchQuery;

$pickupPoints = MondialRelay::searchPickupPoints(new PickupPointSearchQuery(
    country: 'FR',
    postCode: '59000',
    maxResults: 10,
));

foreach ($pickupPoints as $pickupPoint) {
    $pickupPoint->number;                              // "005720"
    $pickupPoint->name;                                // "CARREFOUR EXPRESS"
    $pickupPoint->street;                              // "102 RUE NATIONALE"
    $pickupPoint->latitude;                            // 50.62925
    $pickupPoint->openingHours['monday']->ranges;      // ["08:00-12:30", "14:00-19:00"]
    $pickupPoint->openingHours['sunday']->isClosed();  // true
    $pickupPoint->distanceInMeters;                    // 250
}
```

Searches can also be filtered by `city`, `latitude`/`longitude`, `weightInGrams` and `searchRadiusInKm`.

### Get a single pickup point

```php
$pickupPoint = MondialRelay::pickupPoint('FR', '005720');
```

### Track a parcel

```php
$tracking = MondialRelay::trackParcel('12345678');

$tracking->status;        // TrackingStatus::Delivered
$tracking->isDelivered(); // true
$tracking->statusLabel;   // "Votre colis a été livré"

foreach ($tracking->events as $event) {
    $event->label;      // "Colis enregistré"
    $event->happenedAt; // CarbonImmutable instance
    $event->location;   // "LILLE"
}
```

The tracking language defaults to `mondial-relay-sdk.default_language` (`FR`); pass a second argument to override it: `trackParcel('12345678', 'EN')`.

### Multiple accounts

The facade uses the account configured in `.env`. To use other accounts on the fly, construct the SDK with your own client:

```php
use SmartDato\MondialRelay\MondialRelay;
use SmartDato\MondialRelay\V1\Client;

$sdk = new MondialRelay(new Client(
    enseigne: 'OTHERACC',
    privateKey: 'other-private-key',
));

$sdk->searchPickupPoints(/* ... */);
```

The client defaults to the production API URL; pass a third `url` argument to override it.

### Raw request and response

The raw SOAP exchange is always available — also when a request fails:

```php
use SmartDato\MondialRelay\Exceptions\MondialRelayException;

try {
    $pickupPoints = MondialRelay::searchPickupPoints($query);
} catch (MondialRelayException $exception) {
    $exception->rawRequest;  // raw SOAP envelope that was sent
    $exception->rawResponse; // raw response body (null if the connection failed)
}

// After any call (successful or not):
MondialRelay::lastRawRequest();
MondialRelay::lastRawResponse();
```

### Error handling

All exceptions extend `SmartDato\MondialRelay\Exceptions\MondialRelayException`:

- `MondialRelayConnectionException` — network errors, HTTP errors, unparseable responses
- `MondialRelayWebServiceException` — error status codes returned by the API, with the code available as `$exception->statusCode`

## Testing

```bash
composer test
```

Live integration tests against the Mondial Relay test environment are skipped by default. Run them with:

```bash
MONDIAL_RELAY_LIVE=1 vendor/bin/pest --group=integration
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [SmartDato](https://github.com/smart-dato)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

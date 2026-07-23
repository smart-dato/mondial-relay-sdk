<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SmartDato\MondialRelay\Exceptions\MondialRelayConnectionException;
use SmartDato\MondialRelay\Exceptions\MondialRelayWebServiceException;
use SmartDato\MondialRelay\Facades\MondialRelay;
use SmartDato\MondialRelay\V1\Data\PickupPoint;
use SmartDato\MondialRelay\V1\Enums\TrackingStatus;
use SmartDato\MondialRelay\V1\Queries\PickupPointSearchQuery;

it('searches pickup points', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/point-search-success.xml')))]);

    $points = MondialRelay::searchPickupPoints(new PickupPointSearchQuery(country: 'FR', postCode: '59000'));

    expect($points)->toHaveCount(2)
        ->and($points->first())->toBeInstanceOf(PickupPoint::class)
        ->and($points->first()->number)->toBe('005720')
        ->and($points->first()->name)->toBe('CARREFOUR EXPRESS')
        ->and($points->first()->street)->toBe('102 RUE NATIONALE')
        ->and($points->first()->city)->toBe('LILLE')
        ->and($points->first()->latitude)->toBe(50.62925)
        ->and($points->first()->longitude)->toBe(3.05732)
        ->and($points->first()->distanceInMeters)->toBe(250)
        ->and($points->first()->photoUrl)->toStartWith('https://')
        ->and($points->last()->number)->toBe('012345')
        ->and($points->last()->photoUrl)->toBeNull();
});

it('parses opening hours including split shifts and closed days', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/point-search-success.xml')))]);

    $point = MondialRelay::searchPickupPoints(new PickupPointSearchQuery(country: 'FR'))->first();

    expect($point->openingHours['monday']->ranges)->toBe(['08:00-12:30', '14:00-19:00'])
        ->and($point->openingHours['tuesday']->ranges)->toBe(['08:00-19:00'])
        ->and($point->openingHours['sunday']->isClosed())->toBeTrue();
});

it('returns an empty collection when no pickup points are found', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/point-search-empty.xml')))]);

    $points = MondialRelay::searchPickupPoints(new PickupPointSearchQuery(country: 'FR', postCode: '99999'));

    expect($points)->toBeEmpty();
});

it('sends a correctly signed soap request', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/point-search-empty.xml')))]);

    MondialRelay::searchPickupPoints(new PickupPointSearchQuery(country: 'FR', postCode: '59000'));

    $expectedHash = strtoupper(md5('TESTTESTFR59000030PrivateK'));

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.mondialrelay.com/Web_Services.asmx'
        && $request->hasHeader('SOAPAction', '"http://www.mondialrelay.fr/webservice/WSI4_PointRelais_Recherche"')
        && str_contains($request->body(), '<Enseigne>TESTTEST</Enseigne>')
        && str_contains($request->body(), '<CP>59000</CP>')
        && str_contains($request->body(), '<NombreResultats>30</NombreResultats>')
        && str_contains($request->body(), "<Security>{$expectedHash}</Security>"));
});

it('searches a single pickup point by number', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/point-search-success.xml')))]);

    $point = MondialRelay::pickupPoint('FR', '005720');

    expect($point)->toBeInstanceOf(PickupPoint::class)
        ->and($point->number)->toBe('005720');

    Http::assertSent(fn (Request $request): bool => str_contains($request->body(), '<NumPointRelais>005720</NumPointRelais>')
        && str_contains($request->body(), '<NombreResultats>1</NombreResultats>'));
});

it('tracks a parcel', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/tracking-success.xml')))]);

    $result = MondialRelay::trackParcel('12345678');

    expect($result->status)->toBe(TrackingStatus::Delivered)
        ->and($result->isDelivered())->toBeTrue()
        ->and($result->statusLabel)->toBe('Your parcel has been delivered')
        ->and($result->relayNumber)->toBe('005720')
        ->and($result->events)->toHaveCount(3)
        ->and($result->events[0]->label)->toBe('Parcel registered')
        ->and($result->events[0]->happenedAt->format('Y-m-d H:i'))->toBe('2026-07-20 10:15')
        ->and($result->events[2]->relayNumber)->toBe('005720');
});

it('tracks a parcel with the default language and a signed request', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/tracking-success.xml')))]);

    MondialRelay::trackParcel('12345678');

    $expectedHash = strtoupper(md5('TESTTEST12345678FRPrivateK'));

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('SOAPAction', '"http://www.mondialrelay.fr/webservice/WSI2_TracingColisDetaille"')
        && str_contains($request->body(), '<Expedition>12345678</Expedition>')
        && str_contains($request->body(), '<Langue>FR</Langue>')
        && str_contains($request->body(), "<Security>{$expectedHash}</Security>"));
});

it('tracks a parcel with an explicit language', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/tracking-success.xml')))]);

    MondialRelay::trackParcel('12345678', 'EN');

    Http::assertSent(fn (Request $request): bool => str_contains($request->body(), '<Langue>EN</Langue>'));
});

it('throws a web service exception on an error status code', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/error-invalid-security.xml')))]);

    MondialRelay::searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));
})->throws(MondialRelayWebServiceException::class, 'Mondial Relay API error 97: Invalid security key');

it('exposes the status code on the web service exception', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/error-invalid-security.xml')))]);

    try {
        MondialRelay::searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));
    } catch (MondialRelayWebServiceException $exception) {
        expect($exception->statusCode)->toBe(97);

        return;
    }

    $this->fail('Expected a MondialRelayWebServiceException to be thrown.');
});

it('throws a connection exception on an http error', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response('Server Error', 500)]);

    MondialRelay::searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));
})->throws(MondialRelayConnectionException::class);

it('throws a connection exception on an unparseable response', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response('not xml at all')]);

    MondialRelay::searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));
})->throws(MondialRelayConnectionException::class);

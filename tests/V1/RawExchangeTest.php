<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use SmartDato\MondialRelay\Exceptions\MondialRelayConnectionException;
use SmartDato\MondialRelay\Exceptions\MondialRelayWebServiceException;
use SmartDato\MondialRelay\MondialRelay;
use SmartDato\MondialRelay\V1\Client;
use SmartDato\MondialRelay\V1\Queries\PickupPointSearchQuery;

it('exposes the raw request and response after a successful call', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/point-search-success.xml')))]);

    $client = new Client('TESTTEST', 'PrivateK');

    $client->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));

    expect($client->lastRawRequest())->toContain('<Enseigne>TESTTEST</Enseigne>')
        ->and($client->lastRawRequest())->toContain('<Security>')
        ->and($client->lastRawResponse())->toContain('<STAT>0</STAT>');
});

it('exposes the raw exchange through the sdk entry point', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/tracking-success.xml')))]);

    $sdk = app(MondialRelay::class);

    $sdk->trackParcel('12345678');

    expect($sdk->lastRawRequest())->toContain('<Expedition>12345678</Expedition>')
        ->and($sdk->lastRawResponse())->toContain('<STAT>82</STAT>');
});

it('attaches the raw exchange to web service exceptions', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/error-invalid-security.xml')))]);

    $client = new Client('TESTTEST', 'PrivateK');

    try {
        $client->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));
    } catch (MondialRelayWebServiceException $exception) {
        expect($exception->rawRequest)->toContain('<Enseigne>TESTTEST</Enseigne>')
            ->and($exception->rawResponse)->toContain('<STAT>97</STAT>')
            ->and($client->lastRawRequest())->toBe($exception->rawRequest)
            ->and($client->lastRawResponse())->toBe($exception->rawResponse);

        return;
    }

    $this->fail('Expected a MondialRelayWebServiceException to be thrown.');
});

it('attaches the raw exchange when the api responds with an http error', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response('Server Error', 500)]);

    $client = new Client('TESTTEST', 'PrivateK');

    try {
        $client->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));
    } catch (MondialRelayConnectionException $exception) {
        expect($exception->rawRequest)->toContain('<Enseigne>TESTTEST</Enseigne>')
            ->and($exception->rawResponse)->toBe('Server Error');

        return;
    }

    $this->fail('Expected a MondialRelayConnectionException to be thrown.');
});

it('attaches the raw exchange when the response cannot be parsed', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response('not xml at all')]);

    $client = new Client('TESTTEST', 'PrivateK');

    try {
        $client->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));
    } catch (MondialRelayConnectionException $exception) {
        expect($exception->rawRequest)->toContain('<Enseigne>TESTTEST</Enseigne>')
            ->and($exception->rawResponse)->toBe('not xml at all');

        return;
    }

    $this->fail('Expected a MondialRelayConnectionException to be thrown.');
});

it('attaches the raw request when the connection fails entirely', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    $client = new Client('TESTTEST', 'PrivateK');

    try {
        $client->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));
    } catch (MondialRelayConnectionException $exception) {
        expect($exception->rawRequest)->toContain('<Enseigne>TESTTEST</Enseigne>')
            ->and($exception->rawResponse)->toBeNull()
            ->and($client->lastRawResponse())->toBeNull();

        return;
    }

    $this->fail('Expected a MondialRelayConnectionException to be thrown.');
});

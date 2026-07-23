<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SmartDato\MondialRelay\MondialRelay;
use SmartDato\MondialRelay\V1\Client;
use SmartDato\MondialRelay\V1\Queries\PickupPointSearchQuery;

it('supports multiple accounts on the fly through the constructor', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/point-search-empty.xml')))]);

    $accountOne = new MondialRelay(new Client('ACCOUNT1', 'KeyOne12'));
    $accountTwo = new MondialRelay(new Client('ACCOUNT2', 'KeyTwo34'));

    $accountOne->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));
    $accountTwo->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));

    $hashOne = strtoupper(md5('ACCOUNT1FR030KeyOne12'));
    $hashTwo = strtoupper(md5('ACCOUNT2FR030KeyTwo34'));

    Http::assertSent(fn (Request $request): bool => str_contains($request->body(), '<Enseigne>ACCOUNT1</Enseigne>')
        && str_contains($request->body(), "<Security>{$hashOne}</Security>"));

    Http::assertSent(fn (Request $request): bool => str_contains($request->body(), '<Enseigne>ACCOUNT2</Enseigne>')
        && str_contains($request->body(), "<Security>{$hashTwo}</Security>"));
});

it('uses the production api url by default', function () {
    Http::fake(['*' => Http::response(file_get_contents(fixture('v1/point-search-empty.xml')))]);

    $client = new Client('ACCOUNT1', 'KeyOne12');

    $client->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));

    Http::assertSent(fn (Request $request): bool => $request->url() === Client::DEFAULT_URL);
});

it('accepts a custom api url through the constructor', function () {
    Http::fake(['custom.example.com/*' => Http::response(file_get_contents(fixture('v1/point-search-empty.xml')))]);

    $client = new Client('ACCOUNT1', 'KeyOne12', 'https://custom.example.com/Web_Services.asmx');

    $client->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://custom.example.com/Web_Services.asmx');
});

it('keeps the config-driven account working alongside ad hoc accounts', function () {
    Http::fake(['api.mondialrelay.com/*' => Http::response(file_get_contents(fixture('v1/point-search-empty.xml')))]);

    app(MondialRelay::class)->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));

    $adHoc = new MondialRelay(new Client('ACCOUNT1', 'KeyOne12'));
    $adHoc->searchPickupPoints(new PickupPointSearchQuery(country: 'FR'));

    Http::assertSent(fn (Request $request): bool => str_contains($request->body(), '<Enseigne>TESTTEST</Enseigne>'));
    Http::assertSent(fn (Request $request): bool => str_contains($request->body(), '<Enseigne>ACCOUNT1</Enseigne>'));
});

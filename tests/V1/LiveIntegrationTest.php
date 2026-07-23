<?php

use SmartDato\MondialRelay\V1\Client;
use SmartDato\MondialRelay\V1\Queries\PickupPointSearchQuery;

it('searches pickup points against the live test api', function () {
    $client = new Client(
        enseigne: 'M1ITTEST',
        privateKey: '1dCErPMd',
    );

    $points = $client->searchPickupPoints(new PickupPointSearchQuery(
        country: 'FR',
        postCode: '59000',
        maxResults: 5,
    ));

    expect($points)->not->toBeEmpty()
        ->and($points->first()->number)->not->toBe('')
        ->and($points->first()->latitude)->toBeGreaterThan(0);
})
    ->group('integration')
    ->skip(fn (): bool => getenv('MONDIAL_RELAY_LIVE') !== '1', 'Set MONDIAL_RELAY_LIVE=1 to run live integration tests.');

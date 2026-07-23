<?php

use SmartDato\MondialRelay\V1\Queries\PickupPointSearchQuery;

it('produces all wsdl parameters in order with defaults', function () {
    $query = new PickupPointSearchQuery(country: 'FR');

    expect($query->toParameters())->toBe([
        'Pays' => 'FR',
        'NumPointRelais' => '',
        'Ville' => '',
        'CP' => '',
        'Latitude' => '',
        'Longitude' => '',
        'Taille' => '',
        'Poids' => '',
        'Action' => '',
        'DelaiEnvoi' => '0',
        'RayonRecherche' => '',
        'TypeActivite' => '',
        'NACE' => '',
        'NombreResultats' => 30,
    ]);
});

it('formats coordinates with six decimals and a dot separator', function () {
    $query = new PickupPointSearchQuery(country: 'FR', latitude: 50.62925, longitude: 3.1);

    expect($query->toParameters()['Latitude'])->toBe('50.629250')
        ->and($query->toParameters()['Longitude'])->toBe('3.100000');
});

it('casts weight, radius and max results to strings and ints', function () {
    $query = new PickupPointSearchQuery(
        country: 'BE',
        weightInGrams: 1500,
        searchRadiusInKm: 20,
        maxResults: 5,
    );

    $parameters = $query->toParameters();

    expect($parameters['Poids'])->toBe('1500')
        ->and($parameters['RayonRecherche'])->toBe('20')
        ->and($parameters['NombreResultats'])->toBe(5);
});

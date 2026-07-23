<?php

namespace SmartDato\MondialRelay\V1\Queries;

final readonly class PickupPointSearchQuery
{
    public function __construct(
        public string $country,
        public string $postCode = '',
        public string $city = '',
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?int $weightInGrams = null,
        public ?int $searchRadiusInKm = null,
        public int $maxResults = 30,
        public string $pickupPointNumber = '',
        public string $activityType = '',
    ) {}

    /** @return array<string, string|int> */
    public function toParameters(): array
    {
        return [
            'Pays' => $this->country,
            'NumPointRelais' => $this->pickupPointNumber,
            'Ville' => $this->city,
            'CP' => $this->postCode,
            'Latitude' => $this->formatCoordinate($this->latitude),
            'Longitude' => $this->formatCoordinate($this->longitude),
            'Taille' => '',
            'Poids' => $this->weightInGrams === null ? '' : (string) $this->weightInGrams,
            'Action' => '',
            'DelaiEnvoi' => '0',
            'RayonRecherche' => $this->searchRadiusInKm === null ? '' : (string) $this->searchRadiusInKm,
            'TypeActivite' => $this->activityType,
            'NACE' => '',
            'NombreResultats' => $this->maxResults,
        ];
    }

    protected function formatCoordinate(?float $coordinate): string
    {
        if ($coordinate === null) {
            return '';
        }

        return number_format($coordinate, 6, '.', '');
    }
}

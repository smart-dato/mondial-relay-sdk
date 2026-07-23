<?php

namespace SmartDato\MondialRelay\V1\Data;

use SimpleXMLElement;

final readonly class PickupPoint
{
    /** @param array<string, OpeningHours> $openingHours Keyed by lowercase English day name */
    public function __construct(
        public string $number,
        public string $name,
        public string $addressComplement,
        public string $street,
        public string $streetComplement,
        public string $postCode,
        public string $city,
        public string $country,
        public string $locationHint1,
        public string $locationHint2,
        public float $latitude,
        public float $longitude,
        public string $activityType,
        public string $information,
        public array $openingHours,
        public ?string $photoUrl,
        public ?string $mapUrl,
        public ?int $distanceInMeters,
    ) {}

    public static function fromXml(SimpleXMLElement $node): self
    {
        return new self(
            number: trim((string) $node->Num),
            name: trim((string) $node->LgAdr1),
            addressComplement: trim((string) $node->LgAdr2),
            street: trim((string) $node->LgAdr3),
            streetComplement: trim((string) $node->LgAdr4),
            postCode: trim((string) $node->CP),
            city: trim((string) $node->Ville),
            country: trim((string) $node->Pays),
            locationHint1: trim((string) $node->Localisation1),
            locationHint2: trim((string) $node->Localisation2),
            latitude: self::coordinate((string) $node->Latitude),
            longitude: self::coordinate((string) $node->Longitude),
            activityType: trim((string) $node->TypeActivite),
            information: trim((string) $node->Information),
            openingHours: [
                'monday' => OpeningHours::fromXml($node->Horaires_Lundi),
                'tuesday' => OpeningHours::fromXml($node->Horaires_Mardi),
                'wednesday' => OpeningHours::fromXml($node->Horaires_Mercredi),
                'thursday' => OpeningHours::fromXml($node->Horaires_Jeudi),
                'friday' => OpeningHours::fromXml($node->Horaires_Vendredi),
                'saturday' => OpeningHours::fromXml($node->Horaires_Samedi),
                'sunday' => OpeningHours::fromXml($node->Horaires_Dimanche),
            ],
            photoUrl: trim((string) $node->URL_Photo) ?: null,
            mapUrl: trim((string) $node->URL_Plan) ?: null,
            distanceInMeters: trim((string) $node->Distance) === '' ? null : (int) trim((string) $node->Distance),
        );
    }

    protected static function coordinate(string $value): float
    {
        return (float) str_replace(',', '.', trim($value));
    }
}

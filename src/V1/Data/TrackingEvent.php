<?php

namespace SmartDato\MondialRelay\V1\Data;

use Carbon\CarbonImmutable;
use SimpleXMLElement;

final readonly class TrackingEvent
{
    public function __construct(
        public string $label,
        public ?CarbonImmutable $happenedAt,
        public string $location,
        public string $relayNumber,
        public string $relayCountry,
    ) {}

    public static function fromXml(SimpleXMLElement $node): self
    {
        return new self(
            label: trim((string) $node->Libelle),
            happenedAt: self::parseTimestamp(trim((string) $node->Date), trim((string) $node->Heure)),
            location: trim((string) $node->Emplacement),
            relayNumber: trim((string) $node->Relais_Num),
            relayCountry: trim((string) $node->Relais_Pays),
        );
    }

    protected static function parseTimestamp(string $date, string $time): ?CarbonImmutable
    {
        if ($date === '') {
            return null;
        }

        $timeFormat = strlen($time) === 8 ? 'H:i:s' : 'H:i';

        $timestamp = CarbonImmutable::createFromFormat("d/m/Y {$timeFormat}", "{$date} {$time}");

        return $timestamp ?: null;
    }
}

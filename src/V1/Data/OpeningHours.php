<?php

namespace SmartDato\MondialRelay\V1\Data;

use SimpleXMLElement;

final readonly class OpeningHours
{
    /** @param array<int, string> $ranges Time ranges in "HH:MM-HH:MM" format */
    public function __construct(public array $ranges) {}

    public static function fromXml(SimpleXMLElement $day): self
    {
        $times = [];

        foreach ($day->string as $time) {
            $times[] = (string) $time;
        }

        return self::fromTimes($times);
    }

    /** @param array<int, string> $times Pairs of "HHMM" opening and closing times */
    public static function fromTimes(array $times): self
    {
        $ranges = [];

        foreach (array_chunk($times, 2) as $pair) {
            if (count($pair) !== 2) {
                continue;
            }

            [$opensAt, $closesAt] = $pair;

            if ($opensAt === '0000' && $closesAt === '0000') {
                continue;
            }

            $ranges[] = sprintf(
                '%s:%s-%s:%s',
                substr($opensAt, 0, 2),
                substr($opensAt, 2, 2),
                substr($closesAt, 0, 2),
                substr($closesAt, 2, 2),
            );
        }

        return new self($ranges);
    }

    public function isClosed(): bool
    {
        return $this->ranges === [];
    }
}

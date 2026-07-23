<?php

use SmartDato\MondialRelay\V1\Data\OpeningHours;

it('builds time ranges from hhmm pairs', function () {
    $hours = OpeningHours::fromTimes(['0800', '1230', '1400', '1900']);

    expect($hours->ranges)->toBe(['08:00-12:30', '14:00-19:00'])
        ->and($hours->isClosed())->toBeFalse();
});

it('skips zeroed pairs', function () {
    $hours = OpeningHours::fromTimes(['0800', '1900', '0000', '0000']);

    expect($hours->ranges)->toBe(['08:00-19:00']);
});

it('is closed when all pairs are zeroed', function () {
    $hours = OpeningHours::fromTimes(['0000', '0000', '0000', '0000']);

    expect($hours->isClosed())->toBeTrue();
});

it('ignores a trailing unpaired time', function () {
    $hours = OpeningHours::fromTimes(['0800', '1900', '1000']);

    expect($hours->ranges)->toBe(['08:00-19:00']);
});

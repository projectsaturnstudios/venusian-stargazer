<?php

use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsTimePeriod;

it('reads one instant or start/end/period', function () {
    expect(GibsTimePeriod::parse('2020-01-01'))->toEqual(new GibsTimePeriod('2020-01-01', '2020-01-01', null))
        ->and(GibsTimePeriod::parse('1980-01-01/2023-11-01/P1M'))->toEqual(new GibsTimePeriod('1980-01-01', '2023-11-01', 'P1M'));
});

it('includes only instants on a step from the start, within the ends', function (string $entry, string $when, bool $in) {
    expect(GibsTimePeriod::parse($entry)->includes($when))->toBe($in);
})->with([
    'a single day' => ['2020-01-01', '2020-01-01', true],
    'not that day' => ['2020-01-01', '2020-01-02', false],
    'daily, the start' => ['2000-02-24/2000-04-25/P1D', '2000-02-24', true],
    'daily, the end' => ['2000-02-24/2000-04-25/P1D', '2000-04-25', true],
    'daily, past the end' => ['2000-02-24/2000-04-25/P1D', '2000-04-26', false],
    'daily, before the start' => ['2000-02-24/2000-04-25/P1D', '2000-02-23', false],
    'every 8 days, on a step' => ['2000-01-01/2001-01-01/P8D', '2000-01-17', true],
    'every 8 days, between steps' => ['2000-01-01/2001-01-01/P8D', '2000-01-18', false],
    'monthly, a first' => ['1980-01-01/2023-11-01/P1M', '2001-07-01', true],
    'monthly, mid-month' => ['1980-01-01/2023-11-01/P1M', '2001-07-15', false],
    'yearly' => ['2000-07-01/2020-07-01/P1Y', '2013-07-01', true],
    'yearly, wrong month' => ['2000-07-01/2020-07-01/P1Y', '2013-08-01', false],
    'every 10 minutes' => ['2020-01-01T00:00:00Z/2020-01-02T00:00:00Z/PT10M', '2020-01-01T13:40:00Z', true],
    'between 10-minute steps' => ['2020-01-01T00:00:00Z/2020-01-02T00:00:00Z/PT10M', '2020-01-01T13:45:00Z', false],
    'a month and a day apart' => ['2020-01-01/2020-12-31/P1M1D', '2020-03-03', true],
    'not a month and a day apart' => ['2020-01-01/2020-12-31/P1M1D', '2020-03-02', false],
]);

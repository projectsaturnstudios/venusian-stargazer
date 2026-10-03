<?php

namespace ProjectSaturnStudios\Stargazer\APOD;

use DateTimeImmutable;
use InvalidArgumentException;
use ProjectSaturnStudios\Stargazer\APOD\DataObjects\AstronomyPicture;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\NasaApiService;
use ProjectSaturnStudios\Stargazer\PendingNasaRequest;
use Voyager\NutsAndBolts\Collection;

/**
 * Astronomy Picture of the Day from science.nasa.gov's APOD API (wp-json/wp/v2/apod-basic),
 * which replaced the api.nasa.gov one (archived December 1, 2026). No api_key. Days are
 * addressed as YYMMDD; a list is one page of at most 100 days.
 */
class ApodAPIService extends NasaApiService
{
    /** APOD's first day. */
    public const string FIRST_DAY = '1995-06-16';

    /** The API's largest page. */
    public const int PAGE_LIMIT = 100;

    /**
     * One day's picture; today (in the current timezone) when no date is given.
     * @param string|null $date Y-m-d
     * @return PendingNasaRequest
     */
    public function date(?string $date = null): PendingNasaRequest
    {
        $date ??= now(date_default_timezone_get())->toDateString();

        return $this->pending(
            base: NasaURL::APOD,
            path: self::stamp($date),
            call_name: 'stargazer.apod.date',
            hydrator: AstronomyPicture::class,
        );
    }

    /**
     * Every day from $start to $end (today when null), oldest first.
     * @param string $start Y-m-d
     * @param string|null $end Y-m-d
     * @return PendingNasaRequest
     * @throws InvalidArgumentException When the range spans more than PAGE_LIMIT days.
     */
    public function range(string $start, ?string $end = null): PendingNasaRequest
    {
        $end ??= now(date_default_timezone_get())->toDateString();
        $days = (int) (new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->days + 1;

        if ($days > self::PAGE_LIMIT) {
            throw new InvalidArgumentException("An APOD range is at most ".self::PAGE_LIMIT." days, got {$days}.");
        }

        return $this->pending(
            base: NasaURL::APOD,
            path: '',
            call_name: 'stargazer.apod.range',
            hydrator: self::oldestFirst(...),
            query: ['date_from' => self::stamp($start), 'date_to' => self::stamp($end), 'per_page' => $days],
        );
    }

    /**
     * $count consecutive days from a random point in the archive, oldest first. The API has no
     * random pick, so this asks for a random page of $count days.
     * @param int $count 1 to PAGE_LIMIT
     * @return PendingNasaRequest
     * @throws InvalidArgumentException When $count is outside 1 to PAGE_LIMIT.
     */
    public function count(int $count): PendingNasaRequest
    {
        if ($count < 1 || $count > self::PAGE_LIMIT) {
            throw new InvalidArgumentException('An APOD count is 1 to '.self::PAGE_LIMIT.", got {$count}.");
        }

        $days = (int) (new DateTimeImmutable(self::FIRST_DAY))->diff(new DateTimeImmutable('today'))->days + 1;

        return $this->pending(
            base: NasaURL::APOD,
            path: '',
            call_name: 'stargazer.apod.count',
            hydrator: self::oldestFirst(...),
            query: ['per_page' => $count, 'page' => random_int(1, max(1, intdiv($days, $count)))],
        );
    }

    /**
     * @param mixed $payload The API's list, newest first.
     * @return Collection<int, AstronomyPicture>
     */
    protected static function oldestFirst(mixed $payload): Collection
    {
        return Collection::make(is_array($payload) ? $payload : [])
            ->map(fn (array $day): AstronomyPicture => AstronomyPicture::fromArray($day))
            ->sortBy(fn (AstronomyPicture $picture): string => $picture->date)
            ->values();
    }

    /**
     * @param string $date Y-m-d
     * @return string YYMMDD, the API's address for a day.
     */
    protected static function stamp(string $date): string
    {
        return (new DateTimeImmutable($date))->format('ymd');
    }
}

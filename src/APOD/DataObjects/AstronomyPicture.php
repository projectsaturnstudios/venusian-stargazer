<?php

namespace ProjectSaturnStudios\Stargazer\APOD\DataObjects;

use ProjectSaturnStudios\Stargazer\APOD\APODImageReady;
use ProjectSaturnStudios\Stargazer\APOD\APODMediaFailed;
use ProjectSaturnStudios\Stargazer\APOD\APODVideoReady;
use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;
use Voyager\IOPools\Presumption;

final readonly class AstronomyPicture implements HydratesFromArray
{
    use HydratesNasaData;

    public function __construct(
        public string $date,
        public ?string $title,
        public ?string $explanation,
        public ?string $url,
        public ?string $hdurl,
        public ?string $media_type,
        public ?string $service_version,
        public ?string $copyright,
        public ?string $thumbnail_url,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            date: self::text($data, 'date'),
            title: self::optionalText($data, 'title'),
            explanation: self::optionalText($data, 'explanation'),
            url: self::optionalText($data, 'url'),
            hdurl: self::optionalText($data, 'hdurl'),
            media_type: self::optionalText($data, 'media_type'),
            service_version: self::optionalText($data, 'service_version'),
            copyright: self::optionalText($data, 'copyright'),
            thumbnail_url: self::optionalText($data, 'thumbnail_url'),
        );
    }

    /**
     * What a native view can make of this picture's media: 'picture',
     * 'video' when the url is a real media file, or null on an embed day
     * (YouTube/Vimeo page — nothing a native player can eat).
     */
    public function mediaKind(): ?string
    {
        if ($this->media_type === 'image' && ! is_null($this->url)) {
            return 'picture';
        }

        if ($this->media_type === 'video' && $this->hasDirectMedia()) {
            return 'video';
        }

        return null;
    }

    protected function hasDirectMedia(): bool
    {
        $ext = strtolower(pathinfo(parse_url($this->url ?? '', PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return in_array($ext, ['mp4', 'mov', 'm4v'], true);
    }

    /**
     * Follow this picture's media link through the io pool. The result
     * arrives as mail — APODMediaArrived with the bytes, APODMediaFailed
     * when the conversation goes sour — so a sketch listens instead of
     * plumbing HTTP. Answers the Presumption for hooks (progress and all),
     * the in-flight one when this picture is already downloading, or null
     * on an embed day when there is nothing to fetch.
     */
    public function renderAsync(bool $hd = false): ?Presumption
    {
        $kind = $this->mediaKind();
        if (is_null($kind)) {
            return null;
        }

        $url = ($hd && ! is_null($this->hdurl)) ? $this->hdurl : $this->url;
        $name = "stargazer.apod.media.{$this->date}";
        $http = app('io-pool')->http();

        if (! is_null($in_flight = $http->inFlight($name))) {
            return $in_flight;
        }

        return $http->fetch(
            $name,
            $url,
            envelope: function (HttpResult $result) use ($kind): Completion {
                if (! $result->ok || $result->status >= 400) {
                    return new APODMediaFailed($this, $result, $result->error ?? "Media answered status {$result->status}.");
                }

                return $kind === 'video'
                    ? new APODVideoReady($this, $result)
                    : new APODImageReady($this, $result);
            },
        );
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'title' => $this->title,
            'explanation' => $this->explanation,
            'url' => $this->url,
            'hdurl' => $this->hdurl,
            'media_type' => $this->media_type,
            'service_version' => $this->service_version,
            'thumbnail_url' => $this->thumbnail_url,
        ];
    }
}

<?php

namespace ProjectSaturnStudios\Stargazer\APOD\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\Contracts\IOPools\Promise;

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
     * Fetch this picture's media on the event loop, or null on an embed
     * day when there is nothing to fetch. The promise fulfils with the
     * Http Response; body() is the image or video bytes.
     */
    public function render(bool $hd = false): ?Promise
    {
        if (is_null($this->mediaKind())) {
            return null;
        }

        $url = ($hd && ! is_null($this->hdurl)) ? $this->hdurl : $this->url;

        return app('http')->async()->get($url);
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

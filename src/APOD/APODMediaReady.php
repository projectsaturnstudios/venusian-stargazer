<?php

namespace ProjectSaturnStudios\Stargazer\APOD;

use ProjectSaturnStudios\Stargazer\APOD\DataObjects\AstronomyPicture;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * A picture-of-the-day's media landed: the bytes ride the HttpResult, the
 * picture they belong to rides alongside. Listen on the concrete kind —
 * APODImageReady or APODVideoReady — and the dispatcher does the branching.
 */
abstract readonly class APODMediaReady implements Completion
{
    public string $name;

    public function __construct(
        public AstronomyPicture $apod,
        public HttpResult $result,
    ) {
        $this->name = $result->name;
    }

    public function ok(): bool
    {
        return true;
    }

    /**
     * Write the media bytes to a per-process temp file and answer the path —
     * the one loading story image and video views share.
     */
    public function stash(): string
    {
        $ext = pathinfo(parse_url($this->apod->url ?? '', PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)
            ?: $this->fallbackExtension();

        $path = sys_get_temp_dir().'/apod-'.$this->apod->date.'-'.getmypid().'.'.$ext;
        file_put_contents($path, $this->result->body);

        return $path;
    }

    abstract protected function fallbackExtension(): string;
}

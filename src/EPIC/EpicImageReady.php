<?php

namespace ProjectSaturnStudios\Stargazer\EPIC;

use ProjectSaturnStudios\Stargazer\EPIC\DataObjects\EpicImage;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * An EPIC archive image landed: the bytes ride the HttpResult, the image
 * they belong to rides alongside. stash() hands back a temp file path.
 */
readonly class EpicImageReady implements Completion
{
    public string $name;

    public function __construct(
        public EpicImage $image,
        public HttpResult $result,
        public string $extension,
    ) {
        $this->name = $result->name;
    }

    public function ok(): bool
    {
        return true;
    }

    /**
     * Write the image bytes to a per-process temp file and answer the path.
     */
    public function stash(): string
    {
        $path = sys_get_temp_dir().'/epic-'.$this->image->identifier.'-'.getmypid().'.'.$this->extension;
        file_put_contents($path, $this->result->body);

        return $path;
    }
}

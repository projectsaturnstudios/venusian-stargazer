<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary;

use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageLocation;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * An Image Library sidecar landed: the bytes ride the HttpResult, the
 * location they belong to rides alongside. stash() hands back a temp
 * file path.
 */
readonly class ImageSidecarReady implements Completion
{
    public string $name;

    public function __construct(
        public ImageLocation $location,
        public HttpResult $result,
    ) {
        $this->name = $result->name;
    }

    public function ok(): bool
    {
        return true;
    }

    /**
     * Write the sidecar bytes to a per-process temp file and answer the path.
     */
    public function stash(): string
    {
        $path = sys_get_temp_dir().'/imagelibrary-sidecar-'.crc32($this->location->location).'-'.getmypid();
        file_put_contents($path, $this->result->body);

        return $path;
    }
}

<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary;

use ProjectSaturnStudios\Stargazer\ImageLibrary\DataObjects\ImageLocation;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * An Image Library sidecar download came back unusable. Carries the
 * location it was for and the raw HttpResult so a listener can judge
 * for itself.
 */
readonly class ImageSidecarFailed implements Completion
{
    public string $name;

    public function __construct(
        public ImageLocation $location,
        public HttpResult $result,
        public string $reason,
    ) {
        $this->name = $result->name;
    }

    public function ok(): bool
    {
        return false;
    }
}

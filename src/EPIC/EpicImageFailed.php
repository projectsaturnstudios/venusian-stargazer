<?php

namespace ProjectSaturnStudios\Stargazer\EPIC;

use ProjectSaturnStudios\Stargazer\EPIC\DataObjects\EpicImage;
use Voyager\Contracts\IOPools\Completion;
use Voyager\IOPools\DTO\HttpResult;

/**
 * An EPIC archive download came back unusable. Carries the image it was
 * for and the raw HttpResult so a listener can judge for itself.
 */
readonly class EpicImageFailed implements Completion
{
    public string $name;

    public function __construct(
        public EpicImage $image,
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

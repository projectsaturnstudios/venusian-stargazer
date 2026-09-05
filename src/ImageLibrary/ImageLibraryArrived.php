<?php

namespace ProjectSaturnStudios\Stargazer\ImageLibrary;

use Voyager\Contracts\IOPools\Completion;

/**
 * An Image Library page landed, hydrated. $page is ImageSearchPage,
 * ImageAssetManifest, or ImageLocation; the mail name says which
 * endpoint answered.
 */
readonly class ImageLibraryArrived implements Completion
{
    public function __construct(
        public string $name,
        public object $page,
    ) {}

    public function ok(): bool
    {
        return true;
    }
}

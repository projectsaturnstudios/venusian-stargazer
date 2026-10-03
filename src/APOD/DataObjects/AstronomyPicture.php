<?php

namespace ProjectSaturnStudios\Stargazer\APOD\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;
use ProjectSaturnStudios\Stargazer\Support\HydratesNasaData;
use Voyager\Contracts\IOPools\Promise;

/**
 * One APOD day, from science.nasa.gov's apod-basic API.
 *
 * `url` is the media itself: a screen-sized picture, the mp4 on a video day, or the player's
 * address on an embed day. `hdurl` is the full-size picture (on a video day, its still).
 * `permalink` is the day's article. Explanation and copyright are plain text.
 */
final readonly class AstronomyPicture implements HydratesFromArray
{
    use HydratesNasaData;

    /** The longest side asked of the resizing service for `url`. */
    protected const int SCREEN_SIZE = 1600;

    public function __construct(
        public string $date,
        public ?string $title,
        public ?string $explanation,
        public ?string $url,
        public ?string $hdurl,
        public ?string $media_type,
        public ?string $copyright,
        public ?string $thumbnail_url,
        public ?string $permalink,
        public ?string $alt,
    ) {}

    public static function fromArray(array $data): static
    {
        $media_type = self::optionalText($data, 'media_type');
        $hdurl = self::optionalText($data, 'hdurl');
        $html = (string) ($data['basic_html'] ?? '');
        $is_video = $media_type === 'video';

        return new self(
            date: self::text($data, 'date'),
            title: self::plain(self::optionalText($data, 'title')),
            explanation: self::explanation(self::optionalText($data, 'explanation')),
            url: $is_video ? self::playable($html) : self::screenSized($hdurl),
            hdurl: $hdurl,
            media_type: $media_type,
            copyright: self::credit(self::optionalText($data, 'copyright') ?? self::optionalText($data, 'credit')),
            thumbnail_url: $is_video ? $hdurl : null,
            permalink: self::optionalText($data, 'permalink') ?? self::optionalText($data, 'url'),
            alt: self::plain(self::optionalText($data, 'alt')),
        );
    }

    /**
     * What a native view can make of this day's media: 'picture', 'video' when the url is a real
     * media file, or null on an embed day (a YouTube/Vimeo player — nothing a native player can eat).
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
     * Fetch this day's media on the event loop, or null on an embed day when there is nothing to
     * fetch. The promise fulfils with the Http Response; body() is the image or video bytes.
     */
    public function render(bool $hd = false): ?Promise
    {
        if (is_null($this->mediaKind())) {
            return null;
        }

        $url = ($hd && $this->media_type === 'image' && ! is_null($this->hdurl)) ? $this->hdurl : $this->url;

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
            'copyright' => $this->copyright,
            'thumbnail_url' => $this->thumbnail_url,
            'permalink' => $this->permalink,
            'alt' => $this->alt,
        ];
    }

    /**
     * A video day's file: the page's <video><source>, else its player <iframe>.
     */
    protected static function playable(string $html): ?string
    {
        if (preg_match('/<source[^>]+src="([^"]+)"/i', $html, $match) || preg_match('/<iframe[^>]+src="([^"]+)"/i', $html, $match)) {
            return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
        }

        return null;
    }

    /**
     * The resizing service's URL carries w and h: ask for the picture at screen size. Any other
     * URL is the file itself.
     */
    protected static function screenSized(?string $url): ?string
    {
        if (is_null($url) || ! str_contains($url, '/dynamicimage/')) {
            return $url;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $width = (int) ($query['w'] ?? 0);
        $height = (int) ($query['h'] ?? 0);
        if ($width <= self::SCREEN_SIZE && $height <= self::SCREEN_SIZE || $width === 0 || $height === 0) {
            return $url;
        }

        $scale = self::SCREEN_SIZE / max($width, $height);
        $query['w'] = (int) round($width * $scale);
        $query['h'] = (int) round($height * $scale);

        return strtok($url, '?').'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * "<b> Image Credit: </b> <a>NASA</a> , <a>JPL</a>" as "NASA, JPL".
     */
    protected static function credit(?string $html): ?string
    {
        $text = self::plain($html);
        if (is_null($text)) {
            return null;
        }

        $text = trim((string) preg_replace('/^(Image|Video|Illustration)?\s*Credits?( & Copyright)?\s*:\s*/i', '', $text));

        return $text === '' ? null : $text;
    }

    /**
     * The explanation alone: without its "Explanation:" lead-in, and without the site notices and
     * "Tomorrow's picture" footer that follow it after a double line break.
     */
    protected static function explanation(?string $html): ?string
    {
        if (! is_null($html)) {
            $html = preg_split('/<br\s*\/?>\s*<br\s*\/?>/i', $html)[0];
            $html = preg_replace('/<strong>\s*Tomorrow.{1,8}s picture:.*$/is', '', $html) ?? $html;
        }
        $text = self::plain($html);

        return is_null($text) ? null : (preg_replace('/^Explanation:\s*/i', '', $text) ?: null);
    }

    /**
     * Tags dropped, entities decoded, whitespace collapsed. The source puts a space after every
     * link ("<a>Saturn</a> 's moon <a>Hyperion</a> , once"): close it up before punctuation.
     */
    protected static function plain(?string $html): ?string
    {
        if (is_null($html)) {
            return null;
        }

        $text = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)) ?? '';
        $text = trim(preg_replace("/ (?=[,.;:!?)](?: |$)|['’]s\\b)/u", '', $text) ?? $text);

        return $text === '' ? null : $text;
    }
}

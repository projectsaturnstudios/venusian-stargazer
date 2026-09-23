# Venusian Stargazer

[![Tests](https://github.com/projectsaturnstudios/venusian-stargazer/actions/workflows/tests.yml/badge.svg)](https://github.com/projectsaturnstudios/venusian-stargazer/actions/workflows/tests.yml)

NASA's open APIs for [Venusian](https://github.com/VenusianPHP/framework) apps.

```php
$picture = nasa()->apod()->date('2015-06-03')->get();

echo $picture->title; // "Flyby Image of Saturn's Sponge Moon Hyperion"
```

## Requirements

- PHP 8.4 or 8.5
- A Venusian 0.9 app, or standalone `venusian-voyager/http`, `io-pools` and `nuts-and-bolts` 0.9.1+

## Installation

```bash
composer require projectsaturnstudios/venusian-stargazer
```

After install, call `nasa()`.

Stargazer uses NASA's shared `DEMO_KEY` by default, which is rate-limited to 30 requests an hour. Get a free key at [api.nasa.gov](https://api.nasa.gov) and set it in `.env`:

```dotenv
NASA_API_KEY=your-key-here
```

To publish the config file:

```bash
php computer vendor:publish --tag=nasa-config
```

## Usage

Pick an API family, then an endpoint. Nothing is sent until `get()` or `async()`. `get()` blocks and returns a DTO for a single object, or a `Collection` of DTOs for a list. A non-2xx response throws `StargazerException`. `async()` returns a promise on the event loop that fulfils with what `get()` returns and rejects with what it throws. With no loop bound, `async()` throws `StargazerException`.

```php
$flares = nasa()->donki()->flr('2017-09-06', '2017-09-06')->get();

echo $flares->first()->flrID; // "2017-09-06T08:57:00-FLR-001"
```

### On the event loop

```php
nasa()->neows()->feed()->async()
    ->then(fn ($feed) => printf("%d objects near Earth\n", $feed->element_count))
    ->error(fn (\Throwable $e) => printf("NeoWs failed: %s\n", $e->getMessage()));
```

Two `async()` calls run concurrently. `wait()` resolves each one to its DTOs:

```php
$feed = nasa()->neows()->feed()->async();
$flares = nasa()->donki()->flr()->async();

printf("%d objects, %d flares\n", $feed->wait()->element_count, $flares->wait()->count());
```

### Query parameters

`with()` adds any parameter NASA accepts and returns a new request:

```php
$events = nasa()->eonet()->events()->with('status', 'open')->with('limit', 5)->get();
```

### Fetching media

Some DTOs can fetch their own media on the loop. Each method returns a promise of the HTTP response. The bytes are in `body()`.

| DTO | Method | Notes |
|---|---|---|
| `AstronomyPicture` (APOD) | `render(bool $hd = false)` | Returns `null` when the day's media is an embed, such as a YouTube video. |
| `EpicImage` (EPIC) | `render(EpicCollection $collection, EpicImageType $type = EpicImageType::PNG)` | Builds the archive URL from the image's date. |
| `ImageLocation` (Image Library) | `fetch()` | Follows the `location` pointer returned by `asset()`, `metadata()` and `captions()`. |

```php
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicCollection;
use ProjectSaturnStudios\Stargazer\EPIC\Enums\EpicImageType;

$image = nasa()->epic()->natural()->get()->first();

$image->render(EpicCollection::NATURAL, EpicImageType::JPG)
    ->then(fn ($response) => file_put_contents("{$image->image}.jpg", $response->body()));
```

## Supported APIs

| Accessor | API | Endpoints |
|---|---|---|
| `apod()` | Astronomy Picture of the Day | `date`, `range`, `count` |
| `neows()` | Near Earth Object Web Service | `feed`, `lookup`, `browse` |
| `donki()` | Space Weather Database (DONKI) | `cme`, `cmeAnalysis`, `gst`, `ips`, `flr`, `sep`, `mpc`, `rbe`, `hss`, `wsaEnlilSimulations`, `notifications` |
| `eonet()` | Earth Observatory Natural Event Tracker v3 | `events`, `categories`, `sources`, `layers`, `magnitudes` |
| `epic()` | Earth Polychromatic Imaging Camera | `natural`, `enhanced`, `naturalAvailable`, `enhancedAvailable` |
| `insight()` | InSight Mars Weather | `weather` |
| `tle()` | Two-Line Element sets | `collection`, `search`, `satellite` |
| `techtransfer()` | NASA Technology Transfer | `patent`, `software`, `spinoff` |
| `imageLibrary()` | NASA Image and Video Library | `search`, `asset`, `metadata`, `captions` |

Your key goes to APOD, NeoWs, DONKI, EPIC and InSight.

### Not yet supported

`gibs()`, `trek()`, `exoplanet()`, `openScience()`, `ssc()`, `ssd()` and `techport()` exist and throw `NotYetSupportedException`.

## API reference

Dates are `Y-m-d` strings. A `null` argument is left off the request, so NASA's default applies. Enums live under each family's `Enums` namespace, for example `ProjectSaturnStudios\Stargazer\DONKI\Enums\DonkiCatalog`.

### APOD

```php
nasa()->apod()->range('2015-06-01', '2015-06-03')->get()->pluck('title');
// "Pulsating Aurora over Iceland", "Polaris and Comet Lovejoy", "Flyby Image of Saturn's Sponge Moon Hyperion"
```

| Method | Returns |
|---|---|
| `date(?string $date = null, bool $thumbs = false)` | `AstronomyPicture`. With no date, it asks for today in your app's timezone. |
| `range(string $start_date, ?string $end_date = null, bool $thumbs = false)` | `Collection<AstronomyPicture>` |
| `count(int $count, bool $thumbs = false)` | `Collection<AstronomyPicture>`, picked at random by NASA |

`AstronomyPicture` has `date`, `title`, `explanation`, `url`, `hdurl`, `media_type`, `copyright` and `thumbnail_url`. `$thumbs` asks NASA for `thumbnail_url` on video days.

### NeoWs

```php
$rock = nasa()->neows()->lookup('3542519')->get();

$rock->name;                               // "(2010 PK9)"
$rock->is_potentially_hazardous_asteroid;  // true
```

| Method | Returns |
|---|---|
| `feed(?string $start_date = null, ?string $end_date = null)` | `NeoFeed`: `element_count`, and `near_earth_objects` keyed by date |
| `lookup(string $asteroid_id)` | `NearEarthObject` |
| `browse(?int $page = null, ?int $size = null)` | `NeoBrowse`: `near_earth_objects`, and `page` with `total_elements` and `total_pages` |

`NearEarthObject` has `id`, `name`, `absolute_magnitude_h`, `estimated_diameter`, `is_potentially_hazardous_asteroid`, `close_approach_data`, `orbital_data` and `is_sentry_object`.

### DONKI

Every endpoint takes `$from` and `$to`, sent as `startDate` and `endDate`, and returns a `Collection` of the DTO below.

```php
nasa()->donki()->flr('2017-09-06', '2017-09-10')->get()->pluck('classType');
// "X2.2", "X9.3", "M2.5", ... "X8.2"
```

| Method | Extra arguments | DTO | ID field |
|---|---|---|---|
| `cme()` | | `Cme` | `activityID` |
| `cmeAnalysis()` | | `CmeAnalysis` | `associatedCMEID` |
| `gst()` | | `GeomagneticStorm` | `gstID` |
| `ips()` | `?string $location`, `DonkiCatalog\|string\|null $catalog` | `InterplanetaryShock` | `activityID` |
| `flr()` | `?string $class`, `DonkiCatalog\|string\|null $catalog` | `Flare` | `flrID` |
| `sep()` | | `SolarEnergeticParticle` | `sepID` |
| `mpc()` | | `MagnetopauseCrossing` | `mpcID` |
| `rbe()` | | `RadiationBeltEnhancement` | `rbeID` |
| `hss()` | | `HighSpeedStream` | `hssID` |
| `wsaEnlilSimulations()` | | `WsaEnlilSimulation` | `simulationID` |
| `notifications()` | `DonkiNotificationType\|string\|null $type` | `Notification` | `messageID` |

`DonkiIpsLocation` lists the values `ips()` accepts for `$location`: `Earth`, `Mars`, `MESSENGER`, `STEREO A`, `STEREO B` and `ALL`. Pass its `->value`. `CmeAnalysis` reports its `featureCode` as one of the codes in `DonkiAnalysisFeature`.

### EONET

```php
use ProjectSaturnStudios\Stargazer\EONET\Enums\EonetEventStatus;

nasa()->eonet()->events()->with('status', EonetEventStatus::OPEN)->with('limit', 3)->get()->events->pluck('title');
// "Tropical Cyclone 01B", "Hurricane Polo", "Wildfire Round Prarie, Morehouse, Louisiana"
```

| Method | Returns |
|---|---|
| `events()` | `EonetEventsPage`, with `events` |
| `categories(?string $id = null)` | `EonetCategoriesPage`, with `categories` |
| `sources()` | `EonetSourcesPage`, with `sources` |
| `layers(?string $id = null)` | `EonetLayersPage`, with `categories` |
| `magnitudes()` | `EonetMagnitudesPage`, with `magnitudes` |

EONET filters go through `with()`, for example `status`, `limit`, `days`, `category` and `source`. `EonetEvent` has `id`, `title`, `closed`, `categories`, `sources` and `geometry`.

### EPIC

```php
$images = nasa()->epic()->natural('2015-10-31')->get();

$images->count();          // 12
$images->first()->image;   // "epic_1b_20151031003633"
```

| Method | Returns |
|---|---|
| `natural(?string $date = null)` | `Collection<EpicImage>`. With no date, the most recent day. |
| `enhanced(?string $date = null)` | `Collection<EpicImage>` |
| `naturalAvailable()` | `Collection<EpicAvailableDate>`, each with a `date` |
| `enhancedAvailable()` | `Collection<EpicAvailableDate>` |

`EpicImage` has `identifier`, `caption`, `image`, `date`, `centroid`, the `dscovrPosition`, `lunarPosition` and `sunPosition` vectors, and `attitude`. `archiveUrl()` builds the image URL, and `render()` fetches it.

### InSight

```php
$weather = nasa()->insight()->weather()->get();

$weather->solKeys;  // ["675", "676", "677", "678", "679", "680", "681"]
```

| Method | Returns |
|---|---|
| `weather()` | `InsightWeather`: `solKeys`, `sols` and `validity` |

Each `InsightSol` has `sol`, `season` as an `InsightSeason`, `firstUtc`, `lastUtc`, and `temperature`, `windSpeed`, `pressure` and `windDirection` summaries.

### TLE

```php
$iss = nasa()->tle()->satellite(25544)->get();

$iss->name;   // "ISS (ZARYA)"
$iss->line1;  // "1 25544U 98067A   26265.85181744  .00007689  00000+0  14639-3 0  9994"
```

| Method | Returns |
|---|---|
| `collection()` | `TleCollection`: `totalItems` and `members` |
| `search(string $query)` | `TleCollection` |
| `satellite(int\|string $id)` | `TleRecord`: `satelliteId`, `name`, `date`, `line1` and `line2` |

### TechTransfer

```php
nasa()->techtransfer()->spinoff('battery')->get()->total;  // 142
```

| Method | Returns |
|---|---|
| `patent(string $query)` | `TechTransferPage` |
| `software(string $query)` | `TechTransferPage` |
| `spinoff(string $query)` | `TechTransferPage` |

`TechTransferPage` has `results`, `count`, `total`, `perPage` and `page`. Each `TechTransferRecord` has `title`, `description`, `category`, `center`, `imageUrl` and `detailUrl`.

### Image and Video Library

```php
use ProjectSaturnStudios\Stargazer\ImageLibrary\Enums\ImageMediaType;

$page = nasa()->imageLibrary()->search('apollo 11')->with('media_type', ImageMediaType::IMAGE)->get();

$page->totalHits;                              // 1522
$page->items->first()->data->first()->nasaId;  // "jsc2007e034221"
```

| Method | Returns |
|---|---|
| `search(?string $q = null)` | `ImageSearchPage`: `totalHits`, and `items`, each with `data` and `links` |
| `asset(string $nasa_id)` | `ImageAssetManifest`: `items`, one per file |
| `metadata(string $nasa_id)` | `ImageLocation`, which points at a JSON file |
| `captions(string $nasa_id)` | `ImageLocation`, which points at a caption file |

`metadata()` and `captions()` return where the file lives, not the file itself. `fetch()` gets it:

```php
$location = nasa()->imageLibrary()->metadata('as11-40-5874')->get();

$location->fetch()->wait()->json('AVAIL:Title');
// "Apollo 11 Mission image - Astronaut Edwin Aldrin poses beside th"
```

Each `ImageItemData` has `nasaId`, `title`, `description`, `center`, `dateCreated`, `mediaType` as an `ImageMediaType`, `keywords` and `photographer`.

## Testing

```bash
composer test
```

The suite never calls NASA. It runs against captured JSON fixtures and Http fakes on a real event loop.

## License

MIT. See [LICENSE](LICENSE).

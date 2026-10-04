# Venusian Stargazer

[![Tests](https://github.com/projectsaturnstudios/venusian-stargazer/actions/workflows/tests.yml/badge.svg)](https://github.com/projectsaturnstudios/venusian-stargazer/actions/workflows/tests.yml)

NASA's open APIs for [Venusian](https://github.com/VenusianPHP/framework) apps.

```php
$picture = nasa()->apod()->date('2015-06-03')->get();

echo $picture->title; // "Flyby Image of Saturn's Sponge Moon Hyperion"
```

## Requirements

- PHP 8.4 or 8.5
- A Venusian 0.10 app, or standalone `venusian-voyager/http`, `io-pools` and `nuts-and-bolts` 0.10+
- For GIBS: the `xmlreader`, `simplexml` and `zlib` extensions (bundled with PHP builds)

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

Pick an API family, then an endpoint. Nothing is sent until `get()` or `async()`. `get()` blocks and returns a DTO for a single object, or a `Collection` of DTOs for a list. A non-2xx response throws `StargazerException`, whose `status()` is the HTTP status, so a caller can tell a 429 from a 404 from a 503. `async()` returns a promise on the event loop that fulfils with what `get()` returns and rejects with what it throws. With no loop bound, `async()` throws `StargazerException`.

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
| `AstronomyPicture` (APOD) | `render(bool $hd = false)` | Fetches the screen-sized picture, the full-size `hdurl` with `$hd`, or a video day's mp4. Returns `null` on an embed day, such as a YouTube video. |
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
| `gibs()` | Global Imagery Browse Services | `wmts()`, `wms()`, `twms()`, `colormap`, `legend`, `layerMetadata`, `vectorMetadata`, `vectorStyle` |

Your key goes to NeoWs, EPIC and InSight. APOD comes from science.nasa.gov's APOD API, which replaced the api.nasa.gov one (archived December 1, 2026). DONKI is served by NASA's CCMC (`ccmc.gsfc.nasa.gov/DONKI-API`) since its September 2026 move. Neither takes a key.

### Not yet supported

`trek()`, `exoplanet()`, `openScience()`, `ssc()`, `ssd()` and `techport()` exist and throw `NotYetSupportedException`.

## API reference

Dates are `Y-m-d` strings. A `null` argument is left off the request, so NASA's default applies. Enums live under each family's `Enums` namespace, for example `ProjectSaturnStudios\Stargazer\DONKI\Enums\DonkiCatalog`.

### APOD

```php
nasa()->apod()->range('2015-06-01', '2015-06-03')->get()->pluck('title');
// "Pulsating Aurora over Iceland", "Polaris and Comet Lovejoy", "Flyby Image of Saturn's Sponge Moon Hyperion"
```

| Method | Returns |
|---|---|
| `date(?string $date = null)` | `AstronomyPicture`. With no date, it asks for today in your app's timezone. |
| `range(string $start, ?string $end = null)` | `Collection<AstronomyPicture>`, oldest first. `$end` defaults to today. At most 100 days; more throws `InvalidArgumentException`. |
| `count(int $count)` | `Collection<AstronomyPicture>`: `$count` (1 to 100) consecutive days from a random point in the archive, oldest first. The API has no random pick, so it asks for a random page. |

`AstronomyPicture` has `date`, `title`, `explanation`, `url`, `hdurl`, `media_type`, `copyright`, `thumbnail_url`, `permalink` and `alt`.

- `url` is the media: the picture at screen size (1600 px on its longest side), a video day's mp4, or an embed day's player address.
- `hdurl` is the full-size picture. On a video day it is the still, and `thumbnail_url` carries it too.
- `permalink` is the day's article on science.nasa.gov.
- `title`, `explanation`, `copyright` and `alt` are plain text. The explanation drops its "Explanation:" lead-in and the site notices after it.

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

### GIBS

NASA's Global Imagery Browse Services: daily satellite imagery and data layers as tiles and maps. No key. Images come back as the Http `Response`; decode `body()` with whatever draws them.

```php
use ProjectSaturnStudios\Stargazer\GIBS\DataObjects\GibsBox;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsMapFormat;
use ProjectSaturnStudios\Stargazer\GIBS\Enums\GibsTileFormat;

$wmts = nasa()->gibs()->wmts();                 // EPSG:4326, best imagery
$capabilities = $wmts->capabilities()->get();     // 1,300+ layers, read as a stream
$modis = $capabilities->layer('MODIS_Terra_CorrectedReflectance_TrueColor');
$zoom2 = $capabilities->tileMatrixSetOf($modis->identifier)->matrix(2);

foreach ($zoom2->covering(new GibsBox(-30, 0, 60, 45))?->tiles() ?? [] as [$row, $col]) {
    $jpeg = $wmts->tile($modis->identifier, '250m', 2, $row, $col, GibsTileFormat::JPEG, '2021-09-21')->get()->body();
}

$map = nasa()->gibs()->wms()->map('MODIS_Terra_CorrectedReflectance_TrueColor', new GibsBox(-130, 20, -60, 55), 700, 350, GibsMapFormat::PNG, '2021-09-21')->get();
$fires = $wmts->vectorTile('VIIRS_NOAA20_Thermal_Anomalies_375m_All', '500m', 4, 3, 4, '2020-10-01')->get();
```

| Method | Returns |
|---|---|
| `wmts($projection, $set)->capabilities()` / `tile()` / `vectorTile()` / `domains()` | `WmtsCapabilities` / `Response` / `GibsVectorTile` / `WmtsDomains`; RESTful or KVP |
| `wms(…)->capabilities($version)` / `map()` / `legendGraphic()` | `WmsCapabilities` / `Response` / `Response` |
| `twms(…)->capabilities()` / `tileService()` / `tile($pattern)` / `map()` | `WmsCapabilities` / `TwmsTileService` / `Response` / `Response` |
| `colormap($id, $version)` | `GibsColorMaps` (v1.0 or v1.3) |
| `legend($id, $orientation, $format)` | `Response` (SVG or PNG) |
| `layerMetadata($id)`, `vectorMetadata($id)` | `GibsLayerMetadata` |
| `vectorStyle($id)` | `GibsVectorStyle`, whose layers evaluate their paint and layout for a feature at a zoom |

Projections: `GibsProjection::EPSG4326`, `EPSG3857`, `EPSG3413` (Arctic), `EPSG3031` (Antarctic). Imagery sets: `BEST`, `STANDARD`, `NEAR_REAL_TIME`, `ALL`. Capabilities run to megabytes: hold on to the `WmtsCapabilities` you get, or cache the document at `capabilities()->url()` and rebuild it with `WmtsCapabilities::fromXml()`.

## Testing

```bash
composer test
```

The suite never calls NASA. It runs against captured JSON fixtures and Http fakes on a real event loop.

## License

MIT. See [LICENSE](LICENSE).

# Update Log

## 2026-10-04
* **New**: [GIBS](gibs.md) — WMTS (capabilities, tiles, vector tiles, domains; RESTful or KVP; tile-grid math), WMS 1.1.1/1.3.0, TWMS, colour maps, legends, layer and vector metadata, vector styles with an expression evaluator. Moves from [deferred](deferred-apis.md) to core in [API coverage](api-coverage.md). Version 0.10.1.
* **Update**: [Async lane](async-seam.md) — XML and byte payloads, per-request timeouts.

## 2026-10-02
* **Update**: [APOD](apod.md) — `NasaURL::APOD` moves to science.nasa.gov's `wp-json/wp/v2/apod-basic` (api.nasa.gov APOD archived 2026-12-01); no `api_key`. `date()` addresses `/YYMMDD`; `range($start, $end)` sends `date_from`/`date_to`/`per_page`, oldest first, ≤100 days; `count()` = consecutive days from a random page. `$thumbs` and `service_version` gone; `AstronomyPicture` gains `permalink`, `alt`, a screen-sized `url`, mp4 `url` on video days, plain-text explanation and credit. [Architecture](architecture.md), [API coverage](api-coverage.md) follow.
* **Update**: [TechTransfer](techtransfer.md) — host line corrected to `technology.nasa.gov`, no `api_key`.
* **Update**: [DONKI](donki.md) — `NasaURL::DONKI` moves to CCMC's `https://ccmc.gsfc.nasa.gov/DONKI-API/get` (api.nasa.gov/DONKI now 301s to a news page); no `api_key`. [Architecture](architecture.md), [API coverage](api-coverage.md) follow.
* **Update**: 0.10 — requires `venusian-voyager/*` ^0.10.0 ([index](index.md)); `StargazerException::status()` carries the failed response's HTTP status ([async lane](async-seam.md)).

## 2026-09-23
* **Update**: [Async lane](async-seam.md) — `async()` returns a loop promise of the `get()` DTOs; rejects with `StargazerException`; `render()`/`fetch()` return `Promise<Response>`. Envelope pattern concept deleted; mail classes gone. Family concepts, [API coverage](api-coverage.md), [getting started](getting-started.md) follow. `nasa()` replaces the `NASA` MagicAlias.

## 2026-09-04
* **Update**: Envelope wrap — all nine core families are exemplars. [DONKI](donki.md), [NeoWs](neows.md), [TLE](tle.md), [TechTransfer](techtransfer.md), [APOD](apod.md), [EONET](eonet.md), [EPIC](epic.md), [InSight](insight.md), and [Image and Video Library](image-library.md) name Arrived/Failed (plus APOD/EPIC `renderAsync` and Image Library `fetchAsync`). Async envelope pattern (concept since deleted) dropped “remaining”; [API coverage](api-coverage.md) marks every core row envelope-complete. [Getting started](getting-started.md) now points at `src/MagicAliases/NASA.php` and the `'nasa'` accessor. Deferred stubs unchanged.
* **Update**: [Image and Video Library](image-library.md) re-plated onto the hydrator/envelope lanes — `ImageLibraryArrived` / `ImageLibraryFailed` on search, asset, metadata, and captions; `ImageLocation::fetchAsync()` follows the `{ location }` pointer as `ImageSidecarReady` / `ImageSidecarFailed`; `NASA::imageLibrary()` is on the MagicAlias `@method` block.
* **Creation**: Async envelope pattern (concept since deleted) — two-lane hydrator/envelope recipe with typed `*Arrived`/`*Failed` mail; APOD, InSight, EONET, EPIC converted as exemplars (incl. `EpicImage::renderAsync()` link-follow); test harness rebuilt on `IOPoolDock` + `FakeCurlDriver`. Remaining families follow the recipe verbatim.

## 2026-09-03
* **Update**: `NASA` MagicAlias `@method` block now lists the four envelope-converted accessors — `eonet()`, `apod()`, `epic()`, `insight()` — matching `NasaClient`.
* **Update**: `ApodAPIService::date()` defaults a missing date with `now(date_default_timezone_get())->toDateString()` so APOD requests today in the current timezone rather than UTC — [APOD](apod.md).

## 2026-08-31
* **Creation**: Scaffolded the Venusian Stargazer bundle with `okf_init.py` — see [getting started](getting-started.md).
* **Update**: Replaced the starter concept with a real orientation page and added architecture, async-seam, api-coverage, nine core family concepts, and deferred-api stubs — [architecture](architecture.md), [async seam](async-seam.md), [API coverage](api-coverage.md).
* **Update**: Final audit closed the campaign. Image Library, deferred stubs, and CI landed; `okf_validate --strict` stayed clean (14 concepts); root `GATES.md` reverified ALL MET; output-validator (grok) confirmed the five evaluation criteria after the ledger reverify.

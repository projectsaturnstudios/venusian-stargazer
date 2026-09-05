# Update Log

## 2026-09-04
* **Update**: Envelope wrap — all nine core families are exemplars. [DONKI](donki.md), [NeoWs](neows.md), [TLE](tle.md), [TechTransfer](techtransfer.md), [APOD](apod.md), [EONET](eonet.md), [EPIC](epic.md), [InSight](insight.md), and [Image and Video Library](image-library.md) name Arrived/Failed (plus APOD/EPIC `renderAsync` and Image Library `fetchAsync`). [Async envelope pattern](async-envelope-pattern.md) dropped “remaining”; [API coverage](api-coverage.md) marks every core row envelope-complete. [Getting started](getting-started.md) now points at `src/MagicAliases/NASA.php` and the `'nasa'` accessor. Deferred stubs unchanged.
* **Update**: [Image and Video Library](image-library.md) re-plated onto the hydrator/envelope lanes — `ImageLibraryArrived` / `ImageLibraryFailed` on search, asset, metadata, and captions; `ImageLocation::fetchAsync()` follows the `{ location }` pointer as `ImageSidecarReady` / `ImageSidecarFailed`; `NASA::imageLibrary()` is on the MagicAlias `@method` block.
* **Creation**: [Async envelope pattern](async-envelope-pattern.md) — two-lane hydrator/envelope recipe with typed `*Arrived`/`*Failed` mail; APOD, InSight, EONET, EPIC converted as exemplars (incl. `EpicImage::renderAsync()` link-follow); test harness rebuilt on `IOPoolDock` + `FakeCurlDriver`. Remaining families follow the recipe verbatim.

## 2026-09-03
* **Update**: `NASA` MagicAlias `@method` block now lists the four envelope-converted accessors — `eonet()`, `apod()`, `epic()`, `insight()` — matching `NasaClient`.
* **Update**: `ApodAPIService::date()` defaults a missing date with `now(date_default_timezone_get())->toDateString()` so APOD requests today in the current timezone rather than UTC — [APOD](apod.md).

## 2026-08-31
* **Creation**: Scaffolded the Venusian Stargazer bundle with `okf_init.py` — see [getting started](getting-started.md).
* **Update**: Replaced the starter concept with a real orientation page and added architecture, async-seam, api-coverage, nine core family concepts, and deferred-api stubs — [architecture](architecture.md), [async seam](async-seam.md), [API coverage](api-coverage.md).
* **Update**: Final audit closed the campaign. Image Library, deferred stubs, and CI landed; `okf_validate --strict` stayed clean (14 concepts); root `GATES.md` reverified ALL MET; output-validator (grok) confirmed the five evaluation criteria after the ledger reverify.

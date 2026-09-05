---
type: Playbook
title: Async envelope conversion pattern
description: Two-lane hydrator/envelope recipe with typed mail. All nine core families are finished exemplars.
tags:
  - async
  - envelope
  - io-pools
  - pattern
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: apod
    resource: src/APOD/ApodAPIService.php
    title: APOD exemplar (single-or-list payload)
  - id: insight
    resource: src/InSight/InsightAPIService.php
    title: InSight exemplar (single object payload)
  - id: eonet
    resource: src/EONET/EonetAPIService.php
    title: EONET exemplar (page DTO per endpoint)
  - id: epic
    resource: src/EPIC/EpicAPIService.php
    title: EPIC exemplar (list payload + DTO link-follow)
  - id: techtransfer
    resource: src/TechTransfer/TechTransferAPIService.php
    title: TechTransfer exemplar (one page DTO)
  - id: tle
    resource: src/TLE/TleAPIService.php
    title: TLE exemplar (page or single)
  - id: neows
    resource: src/NeoWs/NeowsAPIService.php
    title: NeoWs exemplar (page or single)
  - id: donki
    resource: src/DONKI/DonkiAPIService.php
    title: DONKI exemplar (list of row DTOs)
  - id: imagelibrary
    resource: src/ImageLibrary/ImageLibraryAPIService.php
    title: Image Library exemplar (page DTO + location follow)
---

# Two lanes, one request

`PendingNasaRequest` carries both lanes. `hydrator` (class-string) serves
sync: `get()` decodes JSON, hydrates, returns DTOs — DO NOT change sync
behavior or sync tests. `envelope` (closure) serves async: the driver
feeds it the `HttpResult`, it answers mail, the mail rides the dock.
No envelope = async mails the raw `HttpResult`. Never pass a class-string
as envelope. Do not invent a shared envelope base class.

# Core exemplars

All nine core families follow this recipe. Copy the closest payload shape.

| Family | Payload shape | Mail payload | Link-follow |
|--------|---------------|--------------|-------------|
| [APOD](apod.md) | single-or-list | `$apods` | `AstronomyPicture::renderAsync()` |
| [InSight](insight.md) | single object | `$weather` | — |
| [EONET](eonet.md) | one page DTO per endpoint | `$page` | — |
| [EPIC](epic.md) | list of rows | `$items` | `EpicImage::renderAsync()` |
| [TechTransfer](techtransfer.md) | one page DTO | `$page` | — (`imageUrl` not followed) |
| [TLE](tle.md) | page or single | `$page` typed as `object` | — |
| [NeoWs](neows.md) | page or single | `$page` typed as `object` | — |
| [DONKI](donki.md) | list of row DTOs | `$items` | — |
| [Image Library](image-library.md) | one object per endpoint | `$page` | `ImageLocation::fetchAsync()` |

The seven deferred stubs (GIBS, Trek, Exoplanet, Open Science, SSC, SSD/CNEOS, Techport) are out of this recipe until those leaves exist. See [deferred APIs](deferred-apis.md).

# Recipe per family

Copy the exemplar closest in payload shape. Shapes: single object →
[InSight](insight.md); single-or-list → [APOD](apod.md); one page DTO per
endpoint → [EONET](eonet.md); list of rows → [EPIC](epic.md).

1. Mail classes in the family folder. `<Family>Arrived` — `readonly`,
   `implements Voyager\Contracts\IOPools\Completion`, props: `string $name`
   plus the hydrated payload (typed DTO, page object, or `array $items`),
   `ok(): true`. `<Family>Failed` — `name`, `HttpResult $result`,
   `string $reason`, `ok(): false`. Copy APODFailed verbatim, rename.
2. Each endpoint method: keep the class-string hydrator, add
   `envelope: fn (HttpResult $result): Completion => static::resolveHttpResult($result)`.
   When endpoints hydrate different DTOs, pass the class through:
   `static::resolveHttpResult($result, TheDto::class)` (see EONET, EPIC).
3. One `resolveHttpResult` per family, protected static. Order is law:
   (a) `! $result->ok || $result->status >= 400` → Failed with
   `$result->error ?? "X answered status {$result->status}."`;
   (b) `json_decode` not array → Failed 'body was not JSON';
   (c) hydrate → Arrived. The envelope ALWAYS answers mail — never null,
   never throw (it runs inside the loop tick).
4. Payload shape: single object → `Dto::fromArray($payload)`; list →
   foreach rows `Dto::fromArray((array) $row)`; single-or-list →
   `array_is_list($payload) ? $payload : [$payload]` (APOD).
5. DTO link-follow (only when a DTO carries a URL or can build one):
   method on the DTO answering `?Presumption`. Copy
   `EpicImage::renderAsync()`: unique call name per item
   (`stargazer.<family>.<thing>.{id}`), `app('io-pool')->http()`,
   `inFlight($name)` coalesce door first, envelope answers
   `<Thing>Ready` (with `stash()` when bytes) or `<Thing>Failed`.
6. Tests, in the family's existing file. Delete the file's local
   `*Pool()` helper and dead imports (`HttpPool`, `EventQueue`,
   `PendingCall`, `HttpDriver`). Sync tests: untouched. Async tests, copy
   from the exemplar files: dispatch test (name + url assertions via
   `stargazerDock()`), Arrived test (stage `stargazerResult(name, fixture)`,
   `$dock->pump()`, `$dock->drain()->sole()` instanceof + payload spot-check
   + `$presumption->settled()`), Failed test (stage `status: 5xx`, assert
   Failed + reason). Link-follow test when step 5 applies (see EPIC).
7. Run `vendor/bin/pest tests/<Family>`. Green = done. Do not touch other
   families' files.

# Harness

`tests/Pest.php`: `stargazerDock()` = bare `IOPoolDock` + recording
`FakeCurlDriver` registered as 'http', bound as 'io-pool' for the `app()`
polyfill (link-followers resolve the pool the way sketches do).
`stargazerResult()` stages a transport conversation. `stargazerClient()`
takes `?PoolService`.

# Related

* [Async seam](async-seam.md) — superseded description, still names the call-name convention.
* [API coverage](api-coverage.md) — nine core families are envelope-complete; seven deferred stubs remain.

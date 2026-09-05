---
type: Reference
title: Stargazer API coverage
description: Core versus deferred status for every NasaURL host Stargazer catalogues. All nine core families are envelope-complete.
tags:
  - coverage
  - nasa
  - status
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: urls
    resource: src/Enums/NasaURL.php
    title: NasaURL enum
  - id: api-nasa
    resource: https://api.nasa.gov/
    title: NASA Open APIs catalog
  - id: envelope
    resource: /async-envelope-pattern.md
    title: Async envelope pattern
---

# Overview

`NasaURL` holds sixteen base URLs. Nine core families have builders, captured fixtures, Pest coverage, and hydrator/envelope lanes. Seven deferred families exist as stubs that throw `NotYetSupportedException`.[^urls][^api-nasa][^envelope]

# Core

Every core row is envelope-complete: `async()` answers `<Family>Arrived` or `<Family>Failed`. Link-follow sidecars are noted where they exist.

| Family | `NasaURL` | Accessor | Envelope | Concept |
|--------|-----------|----------|----------|---------|
| DONKI | `DONKI` | `donki()` | complete (`DonkiArrived` / `DonkiFailed`) | [DONKI](/donki.md) |
| NeoWs | `NEOWS` | `neows()` | complete (`NeowsArrived` / `NeowsFailed`) | [NeoWs](/neows.md) |
| EONET | `EONET` (v3) | `eonet()` | complete (`EonetArrived` / `EonetFailed`) | [EONET](/eonet.md) |
| APOD | `APOD` | `apod()` | complete (`APODArrived` / `APODFailed`; `renderAsync`) | [APOD](/apod.md) |
| EPIC | `EPIC` | `epic()` | complete (`EpicArrived` / `EpicFailed`; `renderAsync`) | [EPIC](/epic.md) |
| InSight | `INSIGHT` | `insight()` | complete (`InsightArrived` / `InsightFailed`) | [InSight](/insight.md) |
| TLE | `TLE` | `tle()` | complete (`TleArrived` / `TleFailed`) | [TLE](/tle.md) |
| TechTransfer | `TECHTRANSFER` | `techtransfer()` | complete (`TechTransferArrived` / `TechTransferFailed`) | [TechTransfer](/techtransfer.md) |
| Image Library | `IMAGE_LIBRARY` | `imageLibrary()` | complete (`ImageLibraryArrived` / `ImageLibraryFailed`; `fetchAsync`) | [Image and Video Library](/image-library.md) |

# Deferred

| Family | `NasaURL` | Accessor | Notes |
|--------|-----------|----------|-------|
| GIBS | `GIBS` | `gibs()` | See [deferred APIs](/deferred-apis.md) |
| Trek WMTS | `TREK_WMTS` | `trek()` | See [deferred APIs](/deferred-apis.md) |
| Exoplanet Archive | `EXOPLANET` | `exoplanet()` | See [deferred APIs](/deferred-apis.md) |
| Open Science | `OPEN_SCIENCE` | `openScience()` | See [deferred APIs](/deferred-apis.md) |
| Satellite Situation Center | `SATELLITE_SITUATION_CENTER` | `ssc()` | See [deferred APIs](/deferred-apis.md) |
| SSD/CNEOS | `SSD_CNEOS` | `ssd()` | See [deferred APIs](/deferred-apis.md) |
| Techport | `TECHPORT` | `techport()` | See [deferred APIs](/deferred-apis.md) |

EONET is v3 (`/api/v3`), not the older v2.1 host. Image Library, EONET, and TLE are not `api.nasa.gov` hosts and do not receive `api_key`.

TechTransfer `imageUrl` follow and Image Library `GET /album/{name}` are not in this pass.

[^urls]: NasaURL enum
[^api-nasa]: NASA Open APIs catalog
[^envelope]: Async envelope pattern

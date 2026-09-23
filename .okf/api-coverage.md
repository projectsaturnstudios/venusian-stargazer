---
type: Reference
title: Stargazer API coverage
description: Core versus deferred status for every NasaURL host Stargazer catalogues. Every core family has an async lane.
tags:
  - coverage
  - nasa
  - status
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: urls
    resource: src/Enums/NasaURL.php
    title: NasaURL enum
  - id: api-nasa
    resource: https://api.nasa.gov/
    title: NASA Open APIs catalog
  - id: seam
    resource: /async-seam.md
    title: Async lane
---

# Overview

`NasaURL` holds sixteen base URLs. Nine core families have builders, captured fixtures, Pest coverage, and an async lane. Seven deferred families exist as stubs that throw `NotYetSupportedException`.[^urls][^api-nasa][^seam]

# Core

Every core family has an async lane: `async()` fulfils with what `get()` returns.

| Family | `NasaURL` | Accessor | Async | Concept |
|--------|-----------|----------|-------|---------|
| DONKI | `DONKI` | `donki()` | promise | [DONKI](/donki.md) |
| NeoWs | `NEOWS` | `neows()` | promise | [NeoWs](/neows.md) |
| EONET | `EONET` (v3) | `eonet()` | promise | [EONET](/eonet.md) |
| APOD | `APOD` | `apod()` | promise | [APOD](/apod.md) |
| EPIC | `EPIC` | `epic()` | promise | [EPIC](/epic.md) |
| InSight | `INSIGHT` | `insight()` | promise | [InSight](/insight.md) |
| TLE | `TLE` | `tle()` | promise | [TLE](/tle.md) |
| TechTransfer | `TECHTRANSFER` | `techtransfer()` | promise | [TechTransfer](/techtransfer.md) |
| Image Library | `IMAGE_LIBRARY` | `imageLibrary()` | promise | [Image and Video Library](/image-library.md) |

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
[^seam]: Async lane

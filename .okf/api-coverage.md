---
type: Reference
title: Stargazer API coverage
description: Core versus deferred status for every NasaURL host Stargazer catalogues.
tags:
  - coverage
  - nasa
  - status
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: urls
    resource: src/Enums/NasaURL.php
    title: NasaURL enum
  - id: api-nasa
    resource: https://api.nasa.gov/
    title: NASA Open APIs catalog
---

# Overview

`NasaURL` holds sixteen base URLs. Nine core families have builders, captured fixtures, and Pest coverage. Seven deferred families exist as stubs that throw `NotYetSupportedException`.[^urls][^api-nasa]

# Core

| Family | `NasaURL` | Accessor | Concept |
|--------|-----------|----------|---------|
| DONKI | `DONKI` | `donki()` | [DONKI](/donki.md) |
| NeoWs | `NEOWS` | `neows()` | [NeoWs](/neows.md) |
| EONET | `EONET` (v3) | `eonet()` | [EONET](/eonet.md) |
| APOD | `APOD` | `apod()` | [APOD](/apod.md) |
| EPIC | `EPIC` | `epic()` | [EPIC](/epic.md) |
| InSight | `INSIGHT` | `insight()` | [InSight](/insight.md) |
| TLE | `TLE` | `tle()` | [TLE](/tle.md) |
| TechTransfer | `TECHTRANSFER` | `techtransfer()` | [TechTransfer](/techtransfer.md) |
| Image Library | `IMAGE_LIBRARY` | `imageLibrary()` | [Image and Video Library](/image-library.md) |

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

[^urls]: NasaURL enum
[^api-nasa]: NASA Open APIs catalog

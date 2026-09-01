---
type: API Family
title: Deferred NASA APIs
description: GIBS, Trek WMTS, Exoplanet Archive, Open Science, SSC, SSD/CNEOS, and Techport stubs.
tags:
  - deferred
  - stubs
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: exception
    resource: src/Exceptions/NotYetSupportedException.php
    title: NotYetSupportedException
  - id: urls
    resource: src/Enums/NasaURL.php
    title: NasaURL deferred cases
  - id: coverage
    resource: /api-coverage.md
    title: API coverage table
---

# Overview

These seven hosts are catalogued on `NasaURL` and exposed as `NasaClient` accessors, but they have no builders, DTOs, or fixtures yet. Constructing the stub (or calling the accessor) throws `NotYetSupportedException::forApi()`.[^exception][^urls]

# Stubs

| Folder | Class | Accessor | `NasaURL` |
|--------|-------|----------|-----------|
| `src/GIBS` | `GibsAPIService` | `gibs()` | `GIBS` |
| `src/Trek` | `TrekWmtsAPIService` | `trek()` | `TREK_WMTS` |
| `src/Exoplanet` | `ExoplanetArchive` | `exoplanet()` | `EXOPLANET` |
| `src/OpenScience` | `OpenScienceAPIService` | `openScience()` | `OPEN_SCIENCE` |
| `src/SSC` | `SscAPIService` | `ssc()` | `SATELLITE_SITUATION_CENTER` |
| `src/SSD` | `SsdCneosAPIService` | `ssd()` | `SSD_CNEOS` |
| `src/Techport` | `TechportAPIService` | `techport()` | `TECHPORT` |

When a leaf is implemented, replace the constructor throw with a real `NasaApiService`, add fixtures under `tests/Fixtures/<Name>/`, and move the row from Deferred to Core in [API coverage](/api-coverage.md).[^coverage]

[^exception]: NotYetSupportedException
[^urls]: NasaURL deferred cases
[^coverage]: API coverage table

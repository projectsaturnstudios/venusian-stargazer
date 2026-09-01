---
type: API Family
title: EPIC
description: DSCOVR Earth Polychromatic Imaging Camera natural and enhanced metadata.
tags:
  - epic
  - earth
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: service
    resource: src/EPIC/EpicAPIService.php
    title: EpicAPIService
---

# Overview

`NASA::epic()` uses `NasaURL::EPIC`. Natural and enhanced imagery share `EpicImage`; available-date lists hydrate `EpicAvailableDate`.[^service]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `natural($date)` | `api/natural` or `api/natural/date/{date}` | `EpicImage` list |
| `enhanced($date)` | `api/enhanced` or `api/enhanced/date/{date}` | `EpicImage` list |
| `naturalAvailable()` | `api/natural/available` | `EpicAvailableDate` list |
| `enhancedAvailable()` | `api/enhanced/available` | `EpicAvailableDate` list |

`EpicImage::archiveUrl()` builds the archive PNG/JPG path from `NasaURL::EPIC` plus `EpicCollection` and `EpicImageType`. Host is `api.nasa.gov`, so `api_key` is appended.

[^service]: EpicAPIService

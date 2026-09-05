---
type: API Family
title: EPIC
description: DSCOVR Earth Polychromatic Imaging Camera — list-of-rows envelope family with archive link-follow.
tags:
  - epic
  - earth
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: service
    resource: src/EPIC/EpicAPIService.php
    title: EpicAPIService
  - id: arrived
    resource: src/EPIC/EpicArrived.php
    title: EpicArrived mail
  - id: image
    resource: src/EPIC/DataObjects/EpicImage.php
    title: EpicImage renderAsync sidecar
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

# Mail

`async()` on every builder keeps the class-string hydrator and adds an envelope. The dock drains `EpicArrived` (`array $items` of `EpicImage` or `EpicAvailableDate`) or `EpicFailed`.[^service][^arrived]

`EpicImage::renderAsync()` follows the archive URL. Mail is `EpicImageReady` (`stash()` writes the bytes) or `EpicImageFailed`.[^image]

# Related

* [Async envelope pattern](/async-envelope-pattern.md) — list-of-rows payload plus DTO link-follow.

[^service]: EpicAPIService
[^arrived]: EpicArrived mail
[^image]: EpicImage renderAsync sidecar

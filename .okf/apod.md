---
type: API Family
title: APOD
description: Astronomy Picture of the Day — single-or-list envelope family with media link-follow.
tags:
  - apod
  - imagery
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: service
    resource: src/APOD/ApodAPIService.php
    title: ApodAPIService
  - id: arrived
    resource: src/APOD/APODArrived.php
    title: APODArrived mail
  - id: picture
    resource: src/APOD/DataObjects/AstronomyPicture.php
    title: AstronomyPicture renderAsync sidecar
---

# Overview

`NASA::apod()` hits `NasaURL::APOD`. A single date hydrates one `AstronomyPicture`; range and count hydrate a Collection of the same DTO. `date()` with a null `$date` sends today in `date_default_timezone_get()`, not UTC.[^service]

# Endpoints

| Builder | Query | DTO |
|---------|-------|-----|
| `date($date, $thumbs)` | `date`, optional `thumbs` | `AstronomyPicture` |
| `range($start_date, $end_date, $thumbs)` | `start_date`, `end_date` | `AstronomyPicture` list |
| `count($count, $thumbs)` | `count` | `AstronomyPicture` list |

Host is `api.nasa.gov`, so `api_key` is appended.

# Mail

`async()` on every builder keeps the class-string hydrator and adds an envelope. The dock drains `APODArrived` (`$apods` is a list of `AstronomyPicture`, one item on a single-date day) or `APODFailed`.[^service][^arrived]

`AstronomyPicture::renderAsync()` follows the picture URL (or `hdurl` when asked). Mail is `APODImageReady` / `APODVideoReady` (`stash()` writes the bytes) or `APODMediaFailed`. Embed days with nothing to fetch return null.[^picture]

# Related

* [Async envelope pattern](/async-envelope-pattern.md) — single-or-list payload plus DTO link-follow.

[^service]: ApodAPIService
[^arrived]: APODArrived mail
[^picture]: AstronomyPicture renderAsync sidecar

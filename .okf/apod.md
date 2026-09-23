---
type: API Family
title: APOD
description: Astronomy Picture of the Day — single-or-list family with media link-follow.
tags:
  - apod
  - imagery
  - core
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: service
    resource: src/APOD/ApodAPIService.php
    title: ApodAPIService
  - id: picture
    resource: src/APOD/DataObjects/AstronomyPicture.php
    title: AstronomyPicture render
---

# Overview

`nasa()->apod()` hits `NasaURL::APOD`. A single date hydrates one `AstronomyPicture`; range and count hydrate a Collection of the same DTO. `date()` with a null `$date` sends today in `date_default_timezone_get()`, not UTC.[^service]

# Endpoints

| Builder | Query | DTO |
|---------|-------|-----|
| `date($date, $thumbs)` | `date`, optional `thumbs` | `AstronomyPicture` |
| `range($start_date, $end_date, $thumbs)` | `start_date`, `end_date` | `AstronomyPicture` list |
| `count($count, $thumbs)` | `count` | `AstronomyPicture` list |

Host is `api.nasa.gov`, so `api_key` is appended.

# Async

`async()` fulfils with one `AstronomyPicture`, or a Collection of them on range and count. A non-success rejects with `StargazerException`.[^service]

`AstronomyPicture::render()` follows the picture URL (or `hdurl` when asked) and returns `Promise<Response>`. Embed days with nothing to fetch return null. `body()` is the bytes.[^picture]

# Related

* [Async lane](/async-seam.md) — single-or-list payload plus DTO link-follow.

[^service]: ApodAPIService
[^picture]: AstronomyPicture render

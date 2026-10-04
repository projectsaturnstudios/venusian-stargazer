---
type: API Family
title: APOD
description: Astronomy Picture of the Day from science.nasa.gov's apod-basic API — single-or-list family with media link-follow.
tags:
  - apod
  - imagery
  - core
status: draft
generated:
  by: claude-opus/5.5
  at: '2026-10-02T23:40:00Z'
sources:
  - id: service
    resource: src/APOD/ApodAPIService.php
    title: ApodAPIService
  - id: picture
    resource: src/APOD/DataObjects/AstronomyPicture.php
    title: AstronomyPicture
  - id: docs
    resource: https://science.nasa.gov/wp-json/wp/v2/apod-basic
    title: science.nasa.gov APOD API (replaces api.nasa.gov/planetary/apod, archived 2026-12-01)
---

# Overview

`nasa()->apod()` hits `NasaURL::APOD`, `https://science.nasa.gov/wp-json/wp/v2/apod-basic`. Not an `api.nasa.gov` host: no `api_key`, no quota spent. Days addressed `YYMMDD`. A list is one page, newest first, at most 100 days (`PAGE_LIMIT`); first day 1995-06-16 (`FIRST_DAY`).[^service][^docs]

# Endpoints

| Builder | Request | DTO |
|---------|---------|-----|
| `date($date)` | path `/YYMMDD`; null `$date` = today in `date_default_timezone_get()`, not UTC | `AstronomyPicture` |
| `range($start, $end)` | `date_from`, `date_to` (`YYMMDD`), `per_page` = day count; null `$end` = today | `AstronomyPicture` list, oldest first |
| `count($count)` | `per_page` = `$count`, `page` = random within the archive | `AstronomyPicture` list, oldest first |

`range()` over 100 days and `count()` outside 1–100 throw `InvalidArgumentException` before sending. API has no random pick: `count()` = `$count` consecutive days from a random page.[^service]

# AstronomyPicture

Hydrates the API's HTML fields into media links and plain text.[^picture]

* `url` — the media. Image day: `hdurl` rescaled to 1600 px longest side when it is a `/dynamicimage/` URL (w/h query), else `hdurl` itself. Video day: `basic_html`'s `<video><source>` mp4, else its player `<iframe>` src.
* `hdurl` — full-size picture; on a video day, the still. `thumbnail_url` = that still on video days, else null.
* `permalink` — the day's science.nasa.gov article.
* `title`, `explanation`, `copyright`, `alt` — tags stripped, entities decoded, whitespace collapsed, the source's space-after-link closed up before punctuation. Explanation cut at its first double `<br>` (site notices, "Tomorrow's picture" footer) and loses its "Explanation:" lead-in. Copyright loses its "Image Credit:" label; falls back to `credit`.
* `mediaKind()` — `'picture'`, `'video'` (url is mp4/mov/m4v), or null on an embed day.

# Async

`async()` fulfils with one `AstronomyPicture`, or a Collection of them on range and count. Non-success rejects with `StargazerException`.[^service]

`render($hd)` follows `url` (`hdurl` with `$hd`, image days only) and returns `Promise<Response>`; `body()` is the bytes. Embed days return null.[^picture]

# Related

* [Async lane](/async-seam.md) — single-or-list payload plus DTO link-follow.

[^service]: ApodAPIService
[^picture]: AstronomyPicture
[^docs]: science.nasa.gov APOD API

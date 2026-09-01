---
type: API Family
title: APOD
description: Astronomy Picture of the Day — single date, inclusive range, or random count.
tags:
  - apod
  - imagery
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: service
    resource: src/APOD/ApodAPIService.php
    title: ApodAPIService
---

# Overview

`NASA::apod()` hits `NasaURL::APOD`. A single date hydrates one `AstronomyPicture`; range and count hydrate a Collection of the same DTO.[^service]

# Endpoints

| Builder | Query | DTO |
|---------|-------|-----|
| `date($date, $thumbs)` | `date`, optional `thumbs` | `AstronomyPicture` |
| `range($start_date, $end_date, $thumbs)` | `start_date`, `end_date` | `AstronomyPicture` list |
| `count($count, $thumbs)` | `count` | `AstronomyPicture` list |

Host is `api.nasa.gov`, so `api_key` is appended.

[^service]: ApodAPIService

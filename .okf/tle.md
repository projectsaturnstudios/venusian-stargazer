---
type: API Family
title: TLE
description: Two-line element satellite catalog — collection, search, and NORAD id lookup.
tags:
  - tle
  - satellites
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: service
    resource: src/TLE/TleAPIService.php
    title: TleAPIService
---

# Overview

`NASA::tle()` uses `NasaURL::TLE`. The host is not `api.nasa.gov`, so no `api_key` is sent.[^service]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `collection()` | `tle` | `TleCollection` |
| `search($query)` | `tle?search=` | `TleCollection` |
| `satellite($id)` | `tle/{id}` | `TleRecord` |

`TleCollection` carries Hydra-style `@context` / `@id` / `@type`, paging parameters, and a `member` list of `TleRecord`.

[^service]: TleAPIService

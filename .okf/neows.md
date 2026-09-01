---
type: API Family
title: NeoWs
description: Near Earth Object Web Service feed, lookup, and browse.
tags:
  - neows
  - asteroids
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: service
    resource: src/NeoWs/NeowsAPIService.php
    title: NeowsAPIService
---

# Overview

`NASA::neows()` talks to `NasaURL::NEOWS`. Feed and browse hydrate page objects; lookup hydrates one `NearEarthObject`.[^service]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `feed($start_date, $end_date)` | `feed` | `NeoFeed` |
| `lookup($asteroid_id)` | `neo/{id}` | `NearEarthObject` |
| `browse($page, $size)` | `neo/browse` | `NeoBrowse` |

Nested DTOs cover close approaches, estimated diameter, relative velocity, miss distance, and orbital data. Host is `api.nasa.gov`, so `api_key` is appended.

[^service]: NeowsAPIService

---
type: API Family
title: NeoWs
description: Near Earth Object Web Service feed, lookup, and browse — page-or-single family.
tags:
  - neows
  - asteroids
  - core
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: service
    resource: src/NeoWs/NeowsAPIService.php
    title: NeowsAPIService
---

# Overview

`nasa()->neows()` talks to `NasaURL::NEOWS`. Feed and browse hydrate page objects; lookup hydrates one `NearEarthObject`.[^service]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `feed($start_date, $end_date)` | `feed` | `NeoFeed` |
| `lookup($asteroid_id)` | `neo/{id}` | `NearEarthObject` |
| `browse($page, $size)` | `neo/browse` | `NeoBrowse` |

Nested DTOs cover close approaches, estimated diameter, relative velocity, miss distance, and orbital data. Host is `api.nasa.gov`, so `api_key` is appended.

# Async

`async()` fulfils with `NeoFeed`, `NearEarthObject`, or `NeoBrowse` — the same value `get()` returns. A non-success rejects with `StargazerException`.[^service]

# Related

* [Async lane](/async-seam.md) — page-or-single payload.

[^service]: NeowsAPIService

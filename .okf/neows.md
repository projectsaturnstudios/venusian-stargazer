---
type: API Family
title: NeoWs
description: Near Earth Object Web Service feed, lookup, and browse — page-or-single envelope family.
tags:
  - neows
  - asteroids
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: service
    resource: src/NeoWs/NeowsAPIService.php
    title: NeowsAPIService
  - id: arrived
    resource: src/NeoWs/NeowsArrived.php
    title: NeowsArrived mail
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

# Mail

`async()` on every builder keeps the class-string hydrator and adds an envelope. The dock drains `NeowsArrived` (`$page` is `NeoFeed`, `NearEarthObject`, or `NeoBrowse`) or `NeowsFailed`.[^service][^arrived]

# Related

* [Async envelope pattern](/async-envelope-pattern.md) — EONET-shaped page-or-single payload.

[^service]: NeowsAPIService
[^arrived]: NeowsArrived mail

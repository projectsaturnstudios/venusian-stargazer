---
type: API Family
title: TLE
description: Two-line element satellite catalog — collection, search, and NORAD id lookup; page-or-single envelope family.
tags:
  - tle
  - satellites
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: service
    resource: src/TLE/TleAPIService.php
    title: TleAPIService
  - id: arrived
    resource: src/TLE/TleArrived.php
    title: TleArrived mail
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

# Mail

`async()` on every builder keeps the class-string hydrator and adds an envelope. The dock drains `TleArrived` (`$page` is `TleCollection` or `TleRecord`) or `TleFailed`.[^service][^arrived]

# Related

* [Async envelope pattern](/async-envelope-pattern.md) — EONET-shaped page-or-single payload.

[^service]: TleAPIService
[^arrived]: TleArrived mail

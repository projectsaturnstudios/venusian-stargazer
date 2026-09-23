---
type: API Family
title: TLE
description: Two-line element satellite catalog — collection, search, and NORAD id lookup; page-or-single family.
tags:
  - tle
  - satellites
  - core
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: service
    resource: src/TLE/TleAPIService.php
    title: TleAPIService
---

# Overview

`nasa()->tle()` uses `NasaURL::TLE`. The host is not `api.nasa.gov`, so no `api_key` is sent.[^service]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `collection()` | `tle` | `TleCollection` |
| `search($query)` | `tle?search=` | `TleCollection` |
| `satellite($id)` | `tle/{id}` | `TleRecord` |

`TleCollection` carries Hydra-style `@context` / `@id` / `@type`, paging parameters, and a `member` list of `TleRecord`.

# Async

`async()` fulfils with `TleCollection` or `TleRecord`. A non-success rejects with `StargazerException`.[^service]

# Related

* [Async lane](/async-seam.md) — page-or-single payload.

[^service]: TleAPIService

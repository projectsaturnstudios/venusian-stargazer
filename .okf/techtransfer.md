---
type: API Family
title: TechTransfer
description: NASA TechTransfer patent, software, and spinoff catalogs — one-page-DTO family.
tags:
  - techtransfer
  - patents
  - core
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: service
    resource: src/TechTransfer/TechTransferAPIService.php
    title: TechTransferAPIService
---

# Overview

`nasa()->techtransfer()` uses `NasaURL::TECHTRANSFER`. Each catalog method takes a search string and hydrates `TechTransferPage` (a count/total/page wrapper around `TechTransferRecord` rows).[^service]

# Endpoints

| Builder | Path | Query key |
|---------|------|-----------|
| `patent($query)` | `patent` | `patent` |
| `software($query)` | `software` | `software` |
| `spinoff($query)` | `spinoff` | `Spinoff` |

`TechTransferCatalog` is the closed set. Host is `api.nasa.gov`, so `api_key` is appended. Records arrive as positional arrays and are mapped field-by-field in `TechTransferRecord::fromArray()`.

# Async

`async()` on every catalog builder fulfils with `TechTransferPage`. A non-success rejects with `StargazerException`. `imageUrl` follow is not in this pass.[^service]

# Related

* [Async lane](/async-seam.md) — one-page-DTO payload.

[^service]: TechTransferAPIService

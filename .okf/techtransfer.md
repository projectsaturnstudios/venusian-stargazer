---
type: API Family
title: TechTransfer
description: NASA TechTransfer patent, software, and spinoff catalogs — one-page-DTO envelope family.
tags:
  - techtransfer
  - patents
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: service
    resource: src/TechTransfer/TechTransferAPIService.php
    title: TechTransferAPIService
  - id: arrived
    resource: src/TechTransfer/TechTransferArrived.php
    title: TechTransferArrived mail
---

# Overview

`NASA::techtransfer()` uses `NasaURL::TECHTRANSFER`. Each catalog method takes a search string and hydrates `TechTransferPage` (a count/total/page wrapper around `TechTransferRecord` rows).[^service]

# Endpoints

| Builder | Path | Query key |
|---------|------|-----------|
| `patent($query)` | `patent` | `patent` |
| `software($query)` | `software` | `software` |
| `spinoff($query)` | `spinoff` | `Spinoff` |

`TechTransferCatalog` is the closed set. Host is `api.nasa.gov`, so `api_key` is appended. Records arrive as positional arrays and are mapped field-by-field in `TechTransferRecord::fromArray()`.

# Mail

`async()` on every catalog builder keeps the class-string hydrator and adds an envelope inside `catalog()`. The dock drains `TechTransferArrived` (`$page` is `TechTransferPage`) or `TechTransferFailed`. `imageUrl` follow is not in this pass.[^service][^arrived]

# Related

* [Async envelope pattern](/async-envelope-pattern.md) — EONET-shaped one-page-DTO payload.

[^service]: TechTransferAPIService
[^arrived]: TechTransferArrived mail

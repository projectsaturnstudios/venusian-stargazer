---
type: API Family
title: TechTransfer
description: NASA TechTransfer patent, software, and spinoff catalogs.
tags:
  - techtransfer
  - patents
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: service
    resource: src/TechTransfer/TechTransferAPIService.php
    title: TechTransferAPIService
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

[^service]: TechTransferAPIService

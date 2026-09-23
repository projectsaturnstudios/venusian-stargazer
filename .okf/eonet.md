---
type: API Family
title: EONET
description: Earth Observatory Natural Event Tracker v3 — one page DTO per endpoint.
tags:
  - eonet
  - earth
  - core
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: service
    resource: src/EONET/EonetAPIService.php
    title: EonetAPIService
---

# Overview

`nasa()->eonet()` uses `NasaURL::EONET` (`/api/v3`, not v2.1). The host is not `api.nasa.gov`, so no `api_key` is sent.[^service]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `events()` | `events` | `EonetEventsPage` |
| `categories($id)` | `categories` or `categories/{id}` | `EonetCategoriesPage` |
| `sources()` | `sources` | `EonetSourcesPage` |
| `layers($id)` | `layers` or `layers/{id}` | `EonetLayersPage` |
| `magnitudes()` | `magnitudes` | `EonetMagnitudesPage` |

Fluent query params (`source()`, `status()`, `limit()`) ride on `PendingNasaRequest`. `EonetEventStatus` is `OPEN` / `CLOSED` / `ALL`.

The campaign example `nasa()->eonet()->categories()->source('InciWeb')->status('open')->async()` fulfils with `EonetCategoriesPage`. A non-success rejects with `StargazerException`.[^service]

# Async

`async()` fulfils with the page DTO `get()` returns (`EonetEventsPage`, `EonetCategoriesPage`, `EonetSourcesPage`, `EonetLayersPage`, or `EonetMagnitudesPage`). A non-success rejects with `StargazerException`.[^service]

# Related

* [Async lane](/async-seam.md) — one page DTO per endpoint.

[^service]: EonetAPIService

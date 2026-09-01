---
type: API Family
title: EONET
description: Earth Observatory Natural Event Tracker v3 — events, categories, sources, layers, magnitudes.
tags:
  - eonet
  - earth
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: service
    resource: src/EONET/EonetAPIService.php
    title: EonetAPIService
---

# Overview

`NASA::eonet()` uses `NasaURL::EONET` (`/api/v3`, not v2.1). The host is not `api.nasa.gov`, so no `api_key` is sent.[^service]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `events()` | `events` | `EonetEventsPage` |
| `categories($id)` | `categories` or `categories/{id}` | `EonetCategoriesPage` |
| `sources()` | `sources` | `EonetSourcesPage` |
| `layers($id)` | `layers` or `layers/{id}` | `EonetLayersPage` |
| `magnitudes()` | `magnitudes` | `EonetMagnitudesPage` |

Fluent query params (`source()`, `status()`, `limit()`) ride on `PendingNasaRequest`. `EonetEventStatus` is `OPEN` / `CLOSED` / `ALL`.

The campaign example `NASA::eonet()->categories()->source('InciWeb')->status('open')->async()` returns a `PendingCall` named `stargazer.eonet.categories`.

[^service]: EonetAPIService

---
type: API Family
title: EONET
description: Earth Observatory Natural Event Tracker v3 — one page DTO per endpoint envelope family.
tags:
  - eonet
  - earth
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: service
    resource: src/EONET/EonetAPIService.php
    title: EonetAPIService
  - id: arrived
    resource: src/EONET/EonetArrived.php
    title: EonetArrived mail
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

The campaign example `NASA::eonet()->categories()->source('InciWeb')->status('open')->async()` dispatches `stargazer.eonet.categories`; the dock drains `EonetArrived` (`$page` is the endpoint page DTO) or `EonetFailed`.[^service][^arrived]

# Mail

`async()` on every builder keeps the class-string hydrator and adds an envelope. The dock drains `EonetArrived` (`$page` is `EonetEventsPage`, `EonetCategoriesPage`, `EonetSourcesPage`, `EonetLayersPage`, or `EonetMagnitudesPage`) or `EonetFailed`.[^service][^arrived]

# Related

* [Async envelope pattern](/async-envelope-pattern.md) — one page DTO per endpoint.

[^service]: EonetAPIService
[^arrived]: EonetArrived mail

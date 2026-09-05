---
type: Architecture
title: Stargazer request architecture
description: Shared builder, DTO, and NasaURL pattern every core NASA API leaf inherits.
tags:
  - architecture
  - stargazer
  - dto
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: pending
    resource: src/PendingNasaRequest.php
    title: PendingNasaRequest
  - id: service
    resource: src/NasaApiService.php
    title: NasaApiService
  - id: hydrator
    resource: src/Contracts/HydratesFromArray.php
    title: HydratesFromArray
  - id: urls
    resource: src/Enums/NasaURL.php
    title: NasaURL host catalog
---

# Overview

The public surface is `NASA` → `NasaClient` → a typed `*APIService` → `PendingNasaRequest`. Services never hard-code hosts; they pass a [`NasaURL`](/api-coverage.md) case into `pending()`.[^urls][^service]

`PendingNasaRequest` carries the endpoint path, query params, a namespaced call name (`stargazer.<api>.<endpoint>`), a hydrator (a DTO class-string or a closure), and an envelope closure. Fluent `with()` / `__call` add query params. `get()` stays sync; `async()` is the [envelope pattern](/async-envelope-pattern.md).[^pending]

# DTOs

DTOs are `final readonly` classes under each family's `DataObjects/` folder. They implement `HydratesFromArray::fromArray()`.[^hydrator] List payloads become a `Voyager\NutsAndBolts\Collection` of DTOs; object payloads become one DTO. Shared coercion lives in `HydratesNasaData` (`text`, `optionalText`, `optionalInt`, `stringList`, `collectionOf`).

Closed value sets are string- or int-backed enums with FULLY UPPERCASE cases. There are no class constants.

# Auth

`api_key` is appended only when `parse_url(NasaURL, PHP_URL_HOST)` is `api.nasa.gov`. EONET, TLE, and Image Library skip it. A missing key falls back to `DEMO_KEY`.

# Schema

| Piece | Role |
|-------|------|
| `NasaURL` | Only place a NASA host literal may live |
| `NasaApiService` | Shared `pending()` / `query()` for every family |
| `PendingNasaRequest` | URL + query + hydrator; `get()` / `async()` |
| `HydratesFromArray` | `fromArray(array $data): static` |

[^pending]: PendingNasaRequest
[^service]: NasaApiService
[^hydrator]: HydratesFromArray
[^urls]: NasaURL host catalog

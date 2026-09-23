---
type: Reference
title: Getting started — Venusian Stargazer
description: How a Venusian sketch reaches NASA through nasa(), NasaClient, a per-API service, and PendingNasaRequest.
tags:
  - getting-started
  - stargazer
  - venusian
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: nasa-helper
    resource: src/helpers.php
    title: nasa() helper
  - id: nasa-client
    resource: src/NasaClient.php
    title: NasaClient accessors
  - id: provider
    resource: src/Providers/StargazerServiceProvider.php
    title: StargazerServiceProvider
---

# Overview

`nasa()` resolves `app('nasa')`, the `NasaClient` singleton. The service provider registers that client and merges `config/nasa.php` (`api_key` from `NASA_API_KEY`, default `DEMO_KEY`).[^nasa-helper][^provider]

A sketch calls a per-API accessor (`donki()`, `eonet()`, `imageLibrary()`, …) and then a builder method. The builder returns a [`PendingNasaRequest`](/architecture.md). `get()` is synchronous; `async()` follows the [async lane](/async-seam.md).[^nasa-client]

Deferred hosts (`gibs()`, `trek()`, …) throw [`NotYetSupportedException`](/deferred-apis.md) until those leaves exist.

# Related

* [Architecture](/architecture.md) — the builder/DTO/enum pattern.
* [API coverage](/api-coverage.md) — which families ship and which are stubs.

[^nasa-helper]: nasa() helper
[^nasa-client]: NasaClient accessors
[^provider]: StargazerServiceProvider

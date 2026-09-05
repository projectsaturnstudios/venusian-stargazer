---
type: Reference
title: Getting started — Venusian Stargazer
description: How a Venusian sketch reaches NASA through NASA, NasaClient, a per-API service, and PendingNasaRequest.
tags:
  - getting-started
  - stargazer
  - venusian
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: nasa-alias
    resource: src/MagicAliases/NASA.php
    title: NASA MagicAlias
  - id: nasa-client
    resource: src/NasaClient.php
    title: NasaClient accessors
  - id: provider
    resource: src/Providers/StargazerServiceProvider.php
    title: StargazerServiceProvider
---

# Overview

`NASA` is a Voyager MagicAlias whose accessor is the `'nasa'` container binding (`NasaClient`). The `@method` block lists the nine core accessors. The service provider registers that client as a container singleton and merges `config/nasa.php` (`api_key` from `NASA_API_KEY`, default `DEMO_KEY`).[^nasa-alias][^provider]

A sketch calls a per-API accessor (`donki()`, `eonet()`, `imageLibrary()`, …) and then a builder method. The builder returns a [`PendingNasaRequest`](/architecture.md). `get()` is synchronous; `async()` follows the [envelope pattern](/async-envelope-pattern.md).[^nasa-client]

Deferred hosts (`gibs()`, `trek()`, …) throw [`NotYetSupportedException`](/deferred-apis.md) until those leaves exist.

# Related

* [Architecture](/architecture.md) — the builder/DTO/enum pattern.
* [API coverage](/api-coverage.md) — which families ship and which are stubs.

[^nasa-alias]: NASA MagicAlias
[^nasa-client]: NasaClient accessors
[^provider]: StargazerServiceProvider

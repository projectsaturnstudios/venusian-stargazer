---
type: API Family
title: InSight
description: Mars InSight lander weather feed (JSON 1.0) — single-object envelope family.
tags:
  - insight
  - mars
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T03:15:00Z'
sources:
  - id: service
    resource: src/InSight/InsightAPIService.php
    title: InsightAPIService
  - id: arrived
    resource: src/InSight/InsightArrived.php
    title: InsightArrived mail
---

# Overview

`NASA::insight()->weather()` calls `NasaURL::INSIGHT` with `feedtype=json` and `ver=1.0`. The payload hydrates `InsightWeather`, which owns per-sol summaries, sensor validity, and wind compass points.[^service]

NASA retired the live InSight feed; the suite replays a captured fixture rather than a live DEMO_KEY call. Host is `api.nasa.gov`, so `api_key` is still appended.

# Mail

`async()` on `weather()` keeps the class-string hydrator and adds an envelope. The dock drains `InsightArrived` (`$weather` is `InsightWeather`) or `InsightFailed`.[^service][^arrived]

# Related

* [Async envelope pattern](/async-envelope-pattern.md) — single-object payload.

[^service]: InsightAPIService
[^arrived]: InsightArrived mail

---
type: API Family
title: InSight
description: Mars InSight lander weather feed (JSON 1.0) — single-object family.
tags:
  - insight
  - mars
  - core
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: service
    resource: src/InSight/InsightAPIService.php
    title: InsightAPIService
---

# Overview

`nasa()->insight()->weather()` calls `NasaURL::INSIGHT` with `feedtype=json` and `ver=1.0`. The payload hydrates `InsightWeather`, which owns per-sol summaries, sensor validity, and wind compass points.[^service]

NASA retired the live InSight feed; the suite replays a captured fixture rather than a live DEMO_KEY call. Host is `api.nasa.gov`, so `api_key` is still appended.

# Async

`async()` on `weather()` fulfils with `InsightWeather`. A non-success rejects with `StargazerException`.[^service]

# Related

* [Async lane](/async-seam.md) — single-object payload.

[^service]: InsightAPIService

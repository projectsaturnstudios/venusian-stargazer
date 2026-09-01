---
type: API Family
title: InSight
description: Mars InSight lander weather feed (JSON 1.0).
tags:
  - insight
  - mars
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: service
    resource: src/InSight/InsightAPIService.php
    title: InsightAPIService
---

# Overview

`NASA::insight()->weather()` calls `NasaURL::INSIGHT` with `feedtype=json` and `ver=1.0`. The payload hydrates `InsightWeather`, which owns per-sol summaries, sensor validity, and wind compass points.[^service]

NASA retired the live InSight feed; the suite replays a captured fixture rather than a live DEMO_KEY call. Host is `api.nasa.gov`, so `api_key` is still appended.

[^service]: InsightAPIService

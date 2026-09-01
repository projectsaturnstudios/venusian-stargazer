---
type: API Family
title: Image and Video Library
description: images-api.nasa.gov search, asset manifest, metadata location, and captions location.
tags:
  - image-library
  - media
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: service
    resource: src/ImageLibrary/ImageLibraryAPIService.php
    title: ImageLibraryAPIService
  - id: docs
    resource: https://images.nasa.gov/docs/images.nasa.gov_api_docs.pdf
    title: images.nasa.gov API documentation v1.22.0
---

# Overview

`NASA::imageLibrary()` uses `NasaURL::IMAGE_LIBRARY`. The host is not `api.nasa.gov`, so no `api_key` is sent.[^service][^docs]

Search and album-style results are Collection+JSON. Metadata and captions return a `{ location }` pointer the caller follows if they want the sidecar JSON or SRT/VTT file.

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `search($q)` | `search` | `ImageSearchPage` |
| `asset($nasa_id)` | `asset/{nasa_id}` | `ImageAssetManifest` |
| `metadata($nasa_id)` | `metadata/{nasa_id}` | `ImageLocation` |
| `captions($nasa_id)` | `captions/{nasa_id}` | `ImageLocation` |

Search accepts the official query params as fluent setters (`media_type`, `page_size`, `center`, `year_start`, …). `ImageMediaType` is `IMAGE` / `VIDEO` / `AUDIO`.

The official docs also list `GET /album/{album_name}`. That builder is not in this leaf; add it when a fixture and Pest example exist.

# Related

* [Architecture](/architecture.md) — Collection+JSON hydrators.
* [Async seam](/async-seam.md) — `stargazer.imagelibrary.*` call names.

[^service]: ImageLibraryAPIService
[^docs]: images.nasa.gov API documentation v1.22.0

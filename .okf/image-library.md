---
type: API Family
title: Image and Video Library
description: images-api.nasa.gov search, asset manifest, metadata location, and captions location, with envelope mail and ImageLocation sidecar follow.
tags:
  - image-library
  - media
  - core
status: draft
generated:
  by: cursor-grok-4.6/2026-09-04
  at: '2026-09-04T02:40:00Z'
sources:
  - id: service
    resource: src/ImageLibrary/ImageLibraryAPIService.php
    title: ImageLibraryAPIService
  - id: location
    resource: src/ImageLibrary/DataObjects/ImageLocation.php
    title: ImageLocation fetchAsync sidecar
  - id: docs
    resource: https://images.nasa.gov/docs/images.nasa.gov_api_docs.pdf
    title: images.nasa.gov API documentation v1.22.0
---

# Overview

`NASA::imageLibrary()` uses `NasaURL::IMAGE_LIBRARY`. The host is not `api.nasa.gov`, so no `api_key` is sent.[^service][^docs]

Search and album-style results are Collection+JSON. Metadata and captions return a `{ location }` pointer; `ImageLocation::fetchAsync()` follows that pointer when the caller wants the sidecar JSON or SRT/VTT bytes.[^location]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `search($q)` | `search` | `ImageSearchPage` |
| `asset($nasa_id)` | `asset/{nasa_id}` | `ImageAssetManifest` |
| `metadata($nasa_id)` | `metadata/{nasa_id}` | `ImageLocation` |
| `captions($nasa_id)` | `captions/{nasa_id}` | `ImageLocation` |

Search accepts the official query params as fluent setters (`media_type`, `page_size`, `center`, `year_start`, …). `ImageMediaType` is `IMAGE` / `VIDEO` / `AUDIO`.

The official docs also list `GET /album/{album_name}`. That builder is not in this leaf; add it when a fixture and Pest example exist.

# Mail

`async()` on every builder keeps the class-string hydrator and adds an envelope. The dock drains `ImageLibraryArrived` (`$page` is the endpoint DTO) or `ImageLibraryFailed`.[^service]

`ImageLocation::fetchAsync()` follows `$this->location` through `app('io-pool')->http()`. The call name is `stargazer.imagelibrary.sidecar.{crc32 of $this->location}` — the DTO has no `nasa_id`, and this pass does not add one. Coalesce via `inFlight($name)`. Mail is `ImageSidecarReady` (`stash()` writes the bytes) or `ImageSidecarFailed`. Search-item hrefs are not followed.[^location]

# Related

* [Architecture](/architecture.md) — Collection+JSON hydrators.
* [Async envelope pattern](/async-envelope-pattern.md) — hydrator/envelope lanes and DTO link-follow.
* [Async seam](/async-seam.md) — `stargazer.imagelibrary.*` call names.

[^service]: ImageLibraryAPIService
[^location]: ImageLocation fetchAsync sidecar
[^docs]: images.nasa.gov API documentation v1.22.0

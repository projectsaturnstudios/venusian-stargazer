---
type: API Family
title: Image and Video Library
description: images-api.nasa.gov search, asset manifest, metadata location, and captions location, with ImageLocation sidecar follow.
tags:
  - image-library
  - media
  - core
status: draft
generated:
  by: claude-opus-5-5
  at: '2026-09-23T16:21:52Z'
sources:
  - id: service
    resource: src/ImageLibrary/ImageLibraryAPIService.php
    title: ImageLibraryAPIService
  - id: location
    resource: src/ImageLibrary/DataObjects/ImageLocation.php
    title: ImageLocation fetch
  - id: docs
    resource: https://images.nasa.gov/docs/images.nasa.gov_api_docs.pdf
    title: images.nasa.gov API documentation v1.22.0
---

# Overview

`nasa()->imageLibrary()` uses `NasaURL::IMAGE_LIBRARY`. The host is not `api.nasa.gov`, so no `api_key` is sent.[^service][^docs]

Search and album-style results are Collection+JSON. Metadata and captions return a `{ location }` pointer; `ImageLocation::fetch()` follows that pointer when the caller wants the sidecar JSON or SRT/VTT bytes.[^location]

# Endpoints

| Builder | Path | DTO |
|---------|------|-----|
| `search($q)` | `search` | `ImageSearchPage` |
| `asset($nasa_id)` | `asset/{nasa_id}` | `ImageAssetManifest` |
| `metadata($nasa_id)` | `metadata/{nasa_id}` | `ImageLocation` |
| `captions($nasa_id)` | `captions/{nasa_id}` | `ImageLocation` |

Search accepts the official query params as fluent setters (`media_type`, `page_size`, `center`, `year_start`, …). `ImageMediaType` is `IMAGE` / `VIDEO` / `AUDIO`.

The official docs also list `GET /album/{album_name}`. That builder is not in this leaf; add it when a fixture and Pest example exist.

# Async

`async()` fulfils with the endpoint DTO (`ImageSearchPage`, `ImageAssetManifest`, or `ImageLocation`). A non-success rejects with `StargazerException`.[^service]

`ImageLocation::fetch()` follows `$this->location` and returns `Promise<Response>`. Spaces in the href are encoded as `%20` at the wire. `callName()` is a label only. Search-item hrefs are not followed.[^location]

# Related

* [Architecture](/architecture.md) — Collection+JSON hydrators.
* [Async lane](/async-seam.md) — promise lane and DTO link-follow.

[^service]: ImageLibraryAPIService
[^location]: ImageLocation fetch
[^docs]: images.nasa.gov API documentation v1.22.0

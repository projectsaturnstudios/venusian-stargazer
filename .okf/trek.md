---
type: API Family
title: Trek WMTS
description: Solar System Treks — Moon, Mars, Vesta layer lists and per-layer RESTful WMTS, read with the GIBS WMTS parser.
tags:
  - trek
  - wmts
  - core
status: draft
generated:
  by: claude-opus/5.5
  at: '2026-10-24T16:42:56Z'
sources:
  - id: service
    resource: src/Trek/TrekWmtsAPIService.php
    title: TrekWmtsAPIService
  - id: layer
    resource: src/Trek/DataObjects/TrekLayer.php
    title: TrekLayer
  - id: body
    resource: src/Trek/Enums/TrekBody.php
    title: TrekBody
  - id: gibs
    resource: /gibs.md
    title: GIBS WMTS types
---

# Overview

`nasa()->trek()` uses `NasaURL::TREK_WMTS` (`https://trek.nasa.gov`). No `api_key`. `TrekBody` is `MOON`, `MARS` or `VESTA`; `TrekProjection` is `EQUIRECTANGULAR` (`EQ`), `NORTH_POLE` (`NP`) or `SOUTH_POLE` (`SP`). Each body knows its CRS per projection, as Trek's API page lists them.[^service][^body]

# Endpoints

| Builder | Path | Answers |
|---------|------|---------|
| `layers($body, $projection)` | Moon, Mars: `{body}/TrekServices/ws/index/{eq\|polar}/searchItems` with Trek's mosaic facets (+ `proj=` for a pole). Vesta: `…/listVisibleLayers?proj=` | `Collection` of `TrekLayer` |
| `capabilities($body, $projection, $label)` | `tiles/{Body}/{EQ\|NP\|SP}/{label}/1.0.0/WMTSCapabilities.xml` | GIBS `WmtsCapabilities` |
| `tile($body, $projection, $label, $zoom, $row, $col, $ext, $set, $style)` | `tiles/{Body}/{P}/{label}/1.0.0/{style}/{set}/{zoom}/{row}/{col}.{ext}` | Http `Response` |

# Rules

- Trek capabilities are standard WMTS; the [GIBS](/gibs.md) `WmtsCapabilities` parser reads them unchanged. The layer's `ResourceURL` says jpg or png; the set is `default028mm`, the style `default`.[^gibs]
- `TrekLayer` carries label, title, body, projection, `box` (`GibsBox` in degrees, null without a `bbox`), mission, instrument, `category` (`productCat1`), `kind` (`productCat2`), coverage and `thumbnailUrl` (Trek's folder prefix + `120.png`). Rows without `productLabel` are skipped.[^layer]
- Vesta's `listVisibleLayers` ignores `rows` and answers everything.
- A tile outside coverage is Trek's 404 → `StargazerException` (status 404).

[^service]: TrekWmtsAPIService
[^layer]: TrekLayer
[^body]: TrekBody
[^gibs]: GIBS WMTS types

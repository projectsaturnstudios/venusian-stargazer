---
type: API Family
title: GIBS
description: Global Imagery Browse Services — WMTS, WMS, TWMS, colour maps, legends, metadata, vector tiles and styles; data, never pixels.
tags:
  - gibs
  - earth
  - core
status: draft
generated:
  by: claude-opus/5.5
  at: '2026-10-04T17:22:32Z'
sources:
  - id: service
    resource: src/GIBS/GibsAPIService.php
    title: GibsAPIService
  - id: wmts
    resource: src/GIBS/Wmts/GibsWmts.php
    title: GibsWmts
  - id: wms
    resource: src/GIBS/Wms/GibsWms.php
    title: GibsWms
  - id: twms
    resource: src/GIBS/Twms/GibsTwms.php
    title: GibsTwms
  - id: vector
    resource: src/GIBS/Vector/GibsVectorTile.php
    title: GibsVectorTile
---

# Overview

`nasa()->gibs()` → `NasaURL::GIBS` (gibs.earthdata.nasa.gov), no `api_key`. Answers data: XML and JSON as data objects, images and tiles as the Http `Response` (`body()` = bytes). Decoding pixels is the app's job (Surface's `app('images')`).[^service]

Every protocol per projection (`GibsProjection`: EPSG4326, EPSG3857, EPSG3413, EPSG3031) and imagery set (`GibsImagerySet`: BEST, STANDARD, NEAR_REAL_TIME, ALL).

# WMTS

`gibs()->wmts($projection, $set)`, each call RESTful or KVP (`GibsRequestStyle`):[^wmts]

| Call | Answers |
|---|---|
| `capabilities()` | `WmtsCapabilities`: layers by id, tile matrix sets by id; streamed (5–13 MB), gzip asked, 120 s timeout |
| `tile(layer, set, zoom, row, col, GibsTileFormat, ?time)` | `Response`; JPEG `.jpeg`, PNG, MVT |
| `vectorTile(…)` | `GibsVectorTile` |
| `domains(layer, set, ?box, ?from, ?to)` | `WmtsDomains`: bbox + time periods. `from` alone = onward, `to` alone = up to |

`WmtsLayer`: formats, sets, `time` (`GibsTimeDimension`: default, periods, `includes()`), ResourceURL templates, styles + legends, metadata links (`linkId('colormap/1.3' | 'layer/1.0' | 'mapbox-gl-style/1.0')`).

`WmtsTileMatrix`: `pixelSpan()` = scale × 0.28 mm ÷ metres per unit (degrees: 111319.49…), `tileBox(row, col)`, `covering(GibsBox)` → `WmtsTileRange`. GIBS 4326 sets share scales per level.

# WMS, TWMS

`gibs()->wms()`: `capabilities(WmsVersion)` (1.1.1 or 1.3.0, named layers at any depth with group), `map(layers, box, w, h, GibsMapFormat, ?time, version, transparent, styles)` (PNG/JPEG/TIFF; GIBS refuses the other listed formats; 1.3.0 EPSG:4326 box latitude first), `legendGraphic(layer)`. GetFeatureInfo is off server side; GetMetadata links answer a directory listing.[^wms]

`gibs()->twms()`: `capabilities()` (1.1.1 shape), `tileService()` (`TwmsTileService`, streamed: groups by layer, `TwmsTilePattern` timed + timeless), `tile(pattern, ?time)`, `map(layer, box, …)`. TWMS answers only listed boxes; the KML generator is gone (404).[^twms]

# Metadata

`colormap(id, GibsColorMapVersion)` → `GibsColorMaps` (v1.0 one map; v1.3 no-data + data maps, legend entries; `entryFor(value)` reads ranges like `[0,0.005)`, ±INF). `legend(id, H|V, SVG|PNG)` → `Response`. `layerMetadata(id)`, `vectorMetadata(id)` → `GibsLayerMetadata` (concept ids, `mvt_properties` as `GibsMvtProperty`). `vectorStyle(id)` → `GibsVectorStyle`.

# Vector

`GibsVectorTile::fromBytes()`: protobuf, gzip inflated first. Features keep tags + geometry encoded; `properties()`, `parts()`, `polygons()` decode on each call. LineTo steps that do not move add no vertex. Fire tile at z1 (26,642 features): 9 MB held; reservoirs (2.7 MB): 3.8 MB held, +10 MB peak.[^vector]

`GibsStyleLayer`: `draws(feature, zoom)`, `paint()`, `layout()`. `GibsStyleExpression` evaluates every operator GIBS's 183 styles use (all evaluate at zooms 0–9) plus the common rest; unknown operator throws. `GibsStyleColor::parse()`: `#rgb`, `#rrggbb`, `rgb()`, `rgba()`, rgba results.

# Errors

Non-2xx → `StargazerException` with `status()`. An OGC exception report in an image's or document's place (WMS sends some at 200) → `serviceException`. KVP queries keep `/ , :` literal (`GibsUrl`): GIBS rejects a `%2F` TIME range.

[^service]: GibsAPIService
[^wmts]: GibsWmts
[^wms]: GibsWms
[^twms]: GibsTwms
[^vector]: GibsVectorTile

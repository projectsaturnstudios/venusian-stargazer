---
okf_version: '0.2'
---

# Venusian Stargazer

NASA API client for Venusian (`projectsaturnstudios/venusian-stargazer` 0.8.0). Start here, then open only the concepts the task needs.

* [Getting started](getting-started.md) - how a sketch reaches NASA through the MagicAlias, client, service, and pending request.

# Architecture

* [Architecture](architecture.md) - builder, DTO, and `NasaURL` pattern every core API shares.
* [Async seam](async-seam.md) - `get()` vs `async()`, `PendingCall`, and the HttpPool binding.
* [API coverage](api-coverage.md) - core vs deferred status table for all sixteen hosts.

# Core API families

* [DONKI](donki.md) - space-weather events (CME, GST, flares, and the rest of the catalog).
* [NeoWs](neows.md) - near-earth object feed, lookup, and browse.
* [EONET](eonet.md) - Earth Observatory Natural Event Tracker v3.
* [APOD](apod.md) - Astronomy Picture of the Day.
* [EPIC](epic.md) - DSCOVR Earth Polychromatic Imaging Camera.
* [InSight](insight.md) - Mars InSight weather feed.
* [TLE](tle.md) - two-line element satellite catalog.
* [TechTransfer](techtransfer.md) - patents, software, and spinoffs.
* [Image and Video Library](image-library.md) - images-api search, asset, metadata, and captions.

# Deferred

* [Deferred APIs](deferred-apis.md) - GIBS, Trek WMTS, Exoplanet, Open Science, SSC, SSD/CNEOS, Techport stubs.

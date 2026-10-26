---
type: API Family
title: Exoplanet Archive
description: NASA Exoplanet Archive TAP — ADQL in, JSON rows out; typed planets from pscomppars.
tags:
  - exoplanet
  - tap
  - core
status: draft
generated:
  by: claude-opus/5.5
  at: '2026-10-24T16:42:56Z'
sources:
  - id: service
    resource: src/Exoplanet/ExoplanetArchive.php
    title: ExoplanetArchive
  - id: planet
    resource: src/Exoplanet/DataObjects/Exoplanet.php
    title: Exoplanet
---

# Overview

`nasa()->exoplanet()` uses `NasaURL::EXOPLANET` (Caltech IPAC TAP). Every call is `GET sync` with `query` (ADQL), `format=json`, and `maxrec` when a limit is given. No `api_key`.[^service]

# Endpoints

| Builder | ADQL | Answers |
|---------|------|---------|
| `adql($adql, $limit)` | as given | `Collection` of rows (arrays by column) |
| `planets($where, $orderBy, $limit)` | `select <COLUMNS> from pscomppars [where] order by disc_year desc, pl_name` | `Collection` of `Exoplanet` |
| `planet($name)` | `… where pl_name = '<name>'`, `'` doubled, `maxrec=1` | `Exoplanet` or null |

`query()` is not the raw builder: `NasaApiService::query()` builds query strings.

# Rules

- Limits go in `maxrec`, never `TOP`: the archive applies `TOP` before `ORDER BY`.
- `pscomppars` is one row per planet; `ps` is one row per published solution. Use `adql()` for `ps` or any other table.
- `Exoplanet` numbers are nullable; the archive leaves unknown values null.[^planet]
- A rejected query is HTTP 400 with a VOTable; `StargazerException` carries status 400 and the body, which names the reason (`ORA-00904: 'NOPE': invalid identifier`).

[^service]: ExoplanetArchive
[^planet]: Exoplanet

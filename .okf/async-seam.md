---
type: Architecture
title: Stargazer async seam
description: get() is Voyager Http sync; async() returns an IOPools PendingCall from a bound HttpPool.
tags:
  - async
  - iopools
  - http
status: draft
generated:
  by: cursor-grok-4.6/2026-08-31
  at: '2026-08-31T04:20:00Z'
sources:
  - id: pending
    resource: src/PendingNasaRequest.php
    title: PendingNasaRequest get and async
  - id: exception
    resource: src/Exceptions/StargazerException.php
    title: StargazerException factory messages
---

# Overview

`PendingNasaRequest::get()` sends a synchronous GET through `Voyager\Http\Client\Factory` (`Http` MagicAlias, or the Factory injected on `NasaClient`). A non-success status throws `StargazerException::requestFailed()`. The JSON body is then hydrated.[^pending]

`async()` does **not** return a Guzzle promise. It resolves an `HttpPool` and dispatches a named `PendingCall`. The caller's loop pumps `tick()`. HttpPool allows one in-flight call per name, so names are namespaced: `stargazer.donki.cme`, `stargazer.eonet.categories`, `stargazer.imagelibrary.search`.

# Pool resolution

1. The `HttpPool` passed into `NasaClient` / `PendingNasaRequest`, if present.
2. The MagicAlias application binding for `HttpPool`.
3. `app()` if that helper exists and the pool is bound.
4. Otherwise `StargazerException::httpPoolNotBound()` with a message that tells the sketch to register the pool before calling `async()`.[^exception]

Tests prove this with an `HttpDriver` fake that records `dispatch()` and never opens `curl_multi`.

# Related

* [Architecture](/architecture.md) — hydrators and `NasaURL`.
* [API coverage](/api-coverage.md) — which families expose builders that return this request.

[^pending]: PendingNasaRequest get and async
[^exception]: StargazerException factory messages

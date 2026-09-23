---
type: Architecture
title: Stargazer async lane
description: get() blocks; async() returns a loop promise of the same DTOs; DTO link-followers return Promise<Response>.
tags: [async, http, loop]
status: draft
generated: { by: claude-opus-5-5, at: '2026-09-23T16:21:52Z' }
sources:
  - id: pending
    resource: src/PendingNasaRequest.php
    title: PendingNasaRequest
  - id: exception
    resource: src/Exceptions/StargazerException.php
    title: StargazerException
---

# Two lanes, one hydrator

`get()` sends through `app('http')` (or the Factory on `NasaClient`), blocks, hydrates. Non-2xx throws `StargazerException`.[^pending]

`async()` sends through the same Factory's loop driver and returns `Voyager\Contracts\IOPools\Promise`. Fulfils with what `get()` returns; rejects with what `get()` throws. No loop bound on the Factory → `StargazerException::loopNotBound()`.[^pending][^exception]

`wait()` on the main stack borrows the loop. Inside `$loop->async()` it suspends the fiber.

# Link-follows

`AstronomyPicture::render(bool $hd = false): ?Promise` — null on an embed day. `EpicImage::render(EpicCollection, EpicImageType = PNG): Promise`. `ImageLocation::fetch(): Promise` — spaces encoded as `%20` at the wire. Each fulfils with the Http `Response`; `body()` is the bytes.

# Call names

`callName()` is a label only (`stargazer.<family>.<endpoint>`). Nothing coalesces on it.

[^pending]: PendingNasaRequest
[^exception]: StargazerException

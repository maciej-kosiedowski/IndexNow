# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`slimad/indexnow` — a framework-agnostic PHP library that queues URLs and submits them to
[IndexNow](https://www.indexnow.org/documentation) endpoints (Bing, Yandex, Seznam, Naver, Yep,
`api.indexnow.org`). It is published on Packagist and consumed by
`slimad/indexnow-laravel` (repo: `maciej-kosiedowski/IndexNow-for-Laravel`).

Runtime dependencies are limited to PSR-17/PSR-18 interfaces on purpose. **Do not add framework
or HTTP-client dependencies to `require`** — framework glue belongs in an integration package.

## Commands

```bash
composer install

composer ci             # cs + phpstan + test + infection (what CI runs)
composer cs             # PHP-CS-Fixer, dry run
composer cs:fix         # PHP-CS-Fixer, write
composer phpstan        # PHPStan level 10 + strict rules over bin/, src/, tests/
composer test           # PHPUnit
composer test:coverage  # PHPUnit + HTML/text coverage (needs pcov or xdebug)
composer infection      # mutation testing, --min-msi=100 --min-covered-msi=100

vendor/bin/phpunit tests/Unit/Job/SubmitJobTest.php   # single file
vendor/bin/phpunit --filter testSplitsLargeQueuesIntoBatches   # single test
```

`composer infection` and `composer test:coverage` need a coverage driver (`pcov` is what CI uses).

## Quality gates that must not regress

CI fails on any of these, and they are the reason the package can be trusted in production:

* **100% line coverage** (`bin/check-coverage.php` over the Clover report) and **100% MSI**.
  A surviving mutant means a test is not specific enough — tighten the assertion rather than
  loosening the threshold.
* **PHPStan level 10** with `phpstan-strict-rules`, no baseline, no `@phpstan-ignore`.
* Tests run in **random order** (`executionOrder="random"`), so no hidden inter-test state.

When adding a branch, add the test that kills its mutants in the same commit. Avoid unreachable
defensive branches — they cannot be covered and will break the gate. (This is why
`Url::host()` narrows the `parse_url()` result with a `@var` docblock instead of an `assert()`
or an unreachable `if`.)

## Architecture

The flow is queue → drain → submit, with every seam behind an interface:

```
IndexNowService::submit(Url)  ->  UrlStore  ->  SubmitJob::run()  ->  IndexNowClient  ->  endpoints
```

* `Service/IndexNowService` — the write side. Only touches `UrlStore`; it does no validation
  against the configured host (that happens when the job runs).
* `Store/UrlStore` — the seam applications replace to survive process boundaries.
  `InMemoryUrlStore` is the only built-in implementation; it deduplicates by URL string.
* `Job/SubmitJob` — the read side. It owns three policies that are easy to break by accident:
  1. queued URLs that do not belong to `IndexNowConfig::$host` are **dropped** (and counted in
     `SubmitJobResult::$discardedUrls`), because leaving them queued would make every future run
     throw and the URL would never leave the store;
  2. the queue is chunked at `SubmitJob::MAX_URLS_PER_REQUEST` (10 000), the protocol limit;
  3. URLs are removed from the store **only when every engine accepted every batch** — a partial
     failure keeps the whole batch for the next run.
* `Client/IndexNowClient` — the transport seam. `HttpIndexNowClient` is PSR-18 only and maps any
  non-2xx response or transport exception onto `SubmitFailedException`.
* `Config/IndexNowConfig` — host plus a name-indexed, duplicate-free map of `SearchEngine`s.
* `ValueObject/*` — `Url`, `Host`, `Key`, `KeyLocation`. All immutable, all validating in the
  constructor, all throwing an `IndexNowException` subclass. `Host` and `Url::host()` both strip
  the optional fully qualified trailing dot so the two comparisons agree.
* `Exception/IndexNowException` — the base class everything thrown by the package extends. Keep
  it that way; callers catch it.

## Conventions

* `declare(strict_types=1)` everywhere; `final` classes unless there is a reason not to.
* Exceptions are constructed through named static factories, never `new` at the call site.
* Native functions are called with a leading backslash inside namespaced code
  (`\sprintf`, `\count`) — PHP-CS-Fixer enforces this.
* Test doubles live in `tests/Support/` and are excluded from coverage and mutation analysis.

## Repository

* GitHub: `maciej-kosiedowski/IndexNow`, default branch `master`
* Packagist: `slimad/indexnow`
* License: MIT

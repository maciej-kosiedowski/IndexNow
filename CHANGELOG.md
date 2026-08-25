# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

* `SubmitJob` splits the queue into batches of at most 10 000 URLs, the limit the IndexNow
  specification puts on a single submission. The batch size is configurable through the
  fourth constructor argument.
* `SubmitJobResult::$discardedUrls` reports how many queued URLs were dropped because they
  do not belong to the configured host.
* `HttpIndexNowClient` accepts a custom `User-Agent` (fourth constructor argument,
  defaulting to `HttpIndexNowClient::DEFAULT_USER_AGENT`).
* `SubmitFailedException` exposes `$endpoint`, `$statusCode` and `$responseBody` so callers
  can react to a rejection without parsing the message.
* `IndexNowConfig` rejects two search engines registered under the same name instead of
  silently keeping only the last one.
* `SearchEngine` rejects an empty engine name.
* Continuous integration: coding standards, static analysis, a PHP 8.2–8.5 test matrix with
  lowest/highest dependencies, mutation testing, a 100% line coverage gate, `composer audit`
  and Dependabot.

### Changed

* **Breaking:** the minimum supported PHP version is now 8.2 (was 8.1).
* `SubmitJob::run()` now drops queued URLs that do not belong to the configured host
  instead of letting `InvalidConfigException` escape. Previously a single foreign URL made
  every subsequent run fail and could never leave the queue.
* `Host` and `Url::host()` normalise the optional fully qualified trailing dot, so
  `example.com.` and `example.com` are treated as the same host.
* Response bodies echoed into `SubmitFailedException` messages are whitespace-collapsed and
  truncated to 500 characters; the full body stays available on the exception.
* PHPStan runs at level 10 (was 9) and the test suite runs in random order.

### Fixed

* `phpunit.xml.dist` no longer requests a coverage report unconditionally, so the test
  suite runs on machines without `pcov`/`xdebug` instead of reporting "No tests executed".

## [1.0.0] - unreleased

Initial public release.

[Unreleased]: https://github.com/maciej-kosiedowski/IndexNow/compare/master...HEAD

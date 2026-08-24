# SlimAD\IndexNow

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](composer.json)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A small, framework-agnostic [IndexNow](https://www.indexnow.org/documentation) client and queue
for PHP 8.1+. Notify Bing, Yandex, Seznam, Naver and Yep about new or updated URLs without
pulling a single framework dependency.

The package is intentionally minimal: it exposes a queue, an HTTP-based submitter, a job that
drains the queue, and a handful of value objects. Framework integrations (e.g. Laravel) live in
separate packages.

## Why?

Search engines other than Google still rely on classical crawling. IndexNow lets us push
URL-changed notifications directly to participating engines so freshly added products or updated
category pages are discovered quickly.

## Installation

```bash
composer require slimad/indexnow
```

You also need any [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client and
[PSR-17](https://www.php-fig.org/psr/psr-17/) factories. For example:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
# or
composer require symfony/http-client nyholm/psr7
```

## Architecture

```
                       +-------------------+
event listener --->    | IndexNowService   |  ---> queues URLs
                       +-------------------+
                                 |
                                 v
                        +-----------------+
                        |    UrlStore     |  (in-memory by default; swap for Redis, DB, ...)
                        +-----------------+
                                 |
            cron / scheduler ----+
                                 |
                                 v
                        +-----------------+      submit per engine
                        |   SubmitJob     |  --------------------------> +------------------+
                        +-----------------+                              | IndexNowClient   |
                                                                         +------------------+
                                                                                 |
                                                                                 v
                                                                         +------------------+
                                                                         | search engines   |
                                                                         +------------------+
```

* `IndexNowService::submit(Url)` adds a URL to the `UrlStore`.
* The store accumulates URLs across the cron interval; duplicates are coalesced.
* `SubmitJob::run()` drains the store, delivers it to every configured search engine, and only
  removes URLs after every engine accepts the batch.

## Quick start

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use GuzzleHttp\Client as GuzzleClient;
use SlimAD\IndexNow\Client\HttpIndexNowClient;
use SlimAD\IndexNow\Config\IndexNowConfig;
use SlimAD\IndexNow\Config\SearchEngine;
use SlimAD\IndexNow\Job\SubmitJob;
use SlimAD\IndexNow\Service\IndexNowService;
use SlimAD\IndexNow\Store\InMemoryUrlStore;
use SlimAD\IndexNow\ValueObject\Host;
use SlimAD\IndexNow\ValueObject\Key;
use SlimAD\IndexNow\ValueObject\KeyLocation;
use SlimAD\IndexNow\ValueObject\Url;

$psr17 = new Psr17Factory();
$httpClient = new GuzzleClient();

$config = new IndexNowConfig(
    new Host('example.com'),
    [
        SearchEngine::bing(
            new Key((string) getenv('INDEXNOW_BING_KEY')),
            KeyLocation::fromString('https://example.com/' . getenv('INDEXNOW_BING_KEY') . '.txt'),
        ),
        SearchEngine::yandex(
            new Key((string) getenv('INDEXNOW_YANDEX_KEY')),
            KeyLocation::fromString('https://example.com/' . getenv('INDEXNOW_YANDEX_KEY') . '.txt'),
        ),
    ],
);

$store = new InMemoryUrlStore();
$service = new IndexNowService($store);
$client = new HttpIndexNowClient($httpClient, $psr17, $psr17);
$job = new SubmitJob($store, $client, $config);

// Triggered by your "category updated" / "product created" listeners:
$service->submit(new Url('https://example.com/products/42'));

// Triggered by your scheduler (cron):
$result = $job->run();

if ($result->hasFailures()) {
    foreach ($result->failures as $failure) {
        // log and let the next tick retry; the queue is preserved on failure
    }
}
```

## Config

`IndexNowConfig` requires:

* a `Host` — your canonical domain (`example.com`)
* one or more `SearchEngine` instances — each with its own `Key` and `KeyLocation`

The package ships with factories for the well-known engines (`SearchEngine::bing`,
`SearchEngine::yandex`, `SearchEngine::seznam`, `SearchEngine::naver`, `SearchEngine::yep`,
`SearchEngine::indexNowApi`) and a generic constructor for custom endpoints.

A typical environment-driven config looks like:

```env
INDEXNOW_HOST=example.com
INDEXNOW_BING_KEY=abcdef0123456789abcdef0123456789
INDEXNOW_YANDEX_KEY=0123456789abcdef0123456789abcdef
```

`KeyLocation` must point at a file on your domain that returns the matching key as `text/plain`,
e.g. `https://example.com/abcdef0123456789abcdef0123456789.txt`.

## Wiring listeners

The package itself does **not** depend on any framework. To wire it into your application:

* On every product creation, call `IndexNowService::submit(new Url($product->url()))`.
* On every category cache invalidation / category update, call
  `IndexNowService::submit(new Url($category->url()))`.

The store deduplicates so calling `submit()` multiple times for the same URL within an interval is
safe.

## Running the job

`SubmitJob::run()` is synchronous and side-effecting. Schedule it with whatever your project uses
(cron, Symfony Scheduler, Laravel Scheduler, supervised worker). The interval is up to you;
IndexNow recommends batching changes within minutes.

`SubmitJob::run()` returns a `SubmitJobResult` with:

* `submittedUrls` — number of URLs in the batch
* `failures` — `list<SubmitFailedException>`, one per engine that rejected the batch or was
  unreachable
* `isSuccess()` / `hasFailures()` helpers

If any engine fails the job keeps the URLs in the store for the next run.

## Custom storage

Replace `InMemoryUrlStore` with anything that implements `UrlStore` to persist URLs across
processes (Redis, database, Cache PSR-6/PSR-16, ...). The interface is small:

```php
interface UrlStore
{
    public function add(Url $url): void;
    /** @return list<Url> */
    public function all(): array;
    public function remove(Url $url): void;
    public function clear(): void;
    public function count(): int;
}
```

## Custom HTTP transport

`HttpIndexNowClient` only depends on PSR-18 / PSR-17. Any compliant client works (Guzzle, Symfony
HTTP Client, Buzz, ...). If you need a different request shape (e.g. the GET form with
`?url=&key=`), implement `IndexNowClient` yourself — `submit(SubmitRequest)` is the entire
contract.

## Quality gates

This package targets:

* PHP 8.1+
* PHPStan **level 9** (with strict rules)
* 100% line and mutation coverage via [Infection](https://infection.github.io/)

Local development:

```bash
composer install
composer phpstan
composer test
composer infection   # requires xdebug or pcov
```

## Roadmap

1. Core package (this repo).
2. `slimad/indexnow-laravel` — service provider, config publishing, event listeners, scheduler
   binding.
3. Integration in the Stamp project.

## License

MIT — see [LICENSE](LICENSE).

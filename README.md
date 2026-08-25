# SlimAD\IndexNow

[![CI](https://github.com/maciej-kosiedowski/IndexNow/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/maciej-kosiedowski/IndexNow/actions/workflows/ci.yml)
[![Security](https://github.com/maciej-kosiedowski/IndexNow/actions/workflows/security.yml/badge.svg?branch=master)](https://github.com/maciej-kosiedowski/IndexNow/actions/workflows/security.yml)
[![Latest stable version](https://img.shields.io/packagist/v/slimad/indexnow.svg)](https://packagist.org/packages/slimad/indexnow)
[![PHP version](https://img.shields.io/packagist/dependency-v/slimad/indexnow/php.svg)](composer.json)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A small, framework-agnostic [IndexNow](https://www.indexnow.org/documentation) client and queue
for PHP 8.2+. Notify Bing, Yandex, Seznam, Naver and Yep about new or updated URLs without
pulling a single framework dependency.

The package is intentionally minimal: it exposes a queue, an HTTP-based submitter, a job that
drains the queue, and a handful of value objects. Framework integrations live in separate
packages — for Laravel use
[`slimad/indexnow-laravel`](https://github.com/maciej-kosiedowski/IndexNow-for-Laravel).

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
                        +-----------------+      submit per engine, <= 10 000 URLs per call
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
use GuzzleHttp\Client as GuzzleClient;
use Nyholm\Psr7\Factory\Psr17Factory;
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

$key = (string) getenv('INDEXNOW_KEY');

$config = new IndexNowConfig(
    new Host('example.com'),
    [
        SearchEngine::indexNowApi(
            new Key($key),
            KeyLocation::fromString('https://example.com/' . $key . '.txt'),
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
        error_log($failure->getMessage());
    }
}
```

## Config

`IndexNowConfig` requires:

* a `Host` — your canonical domain (`example.com`)
* one or more `SearchEngine` instances — each with its own `Key` and `KeyLocation`

The package ships with factories for the well-known engines (`SearchEngine::bing`,
`SearchEngine::yandex`, `SearchEngine::seznam`, `SearchEngine::naver`, `SearchEngine::yep`,
`SearchEngine::indexNowApi`) and a generic constructor for custom endpoints. Engine names have
to be unique within one `IndexNowConfig`.

> Participating engines forward submissions to each other, so a single endpoint —
> `SearchEngine::indexNowApi()` — is usually enough. Configure several engines only when you
> deliberately want to notify them independently, for example because each one was verified with
> a different key.

A typical environment-driven config looks like:

```env
INDEXNOW_HOST=example.com
INDEXNOW_KEY=abcdef0123456789abcdef0123456789
```

`KeyLocation` must point at a file on your domain that returns the matching key as `text/plain`,
e.g. `https://example.com/abcdef0123456789abcdef0123456789.txt`. The key is deliberately public:
it is an ownership proof, not a credential.

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
* `discardedUrls` — number of queued URLs that were dropped because they do not belong to the
  configured host
* `failures` — `list<SubmitFailedException>`, one per engine that rejected the batch or was
  unreachable
* `isSuccess()` / `hasFailures()` helpers

If any engine fails the job keeps the URLs in the store for the next run.

### Batching

The IndexNow specification caps a single submission at 10 000 URLs. `SubmitJob` chunks the queue
accordingly; pass a smaller batch size if you prefer smaller requests:

```php
$job = new SubmitJob($store, $client, $config, batchSize: 500);
```

### Foreign URLs

IndexNow only accepts URLs that belong to the submitted host. Anything queued for a different
host is dropped by `SubmitJob::run()` and counted in `SubmitJobResult::$discardedUrls`, so one
mistyped URL cannot block the queue forever.

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
HTTP Client, Buzz, ...). It sends a `User-Agent` identifying this package; override it to identify
your own application:

```php
$client = new HttpIndexNowClient($httpClient, $psr17, $psr17, 'acme-shop/2.1');
```

If you need a different request shape (e.g. the GET form with `?url=&key=`), implement
`IndexNowClient` yourself — `submit(SubmitRequest)` is the entire contract.

## Error handling

Everything the package throws extends `SlimAD\IndexNow\Exception\IndexNowException`:

| Exception                 | Thrown when                                                        |
| ------------------------- | ------------------------------------------------------------------ |
| `InvalidUrlException`     | a value is not an absolute `http`/`https` URL                        |
| `InvalidHostException`    | a value is not a valid host name                                     |
| `InvalidKeyException`     | a key is shorter than 8 / longer than 128 chars or has bad characters |
| `InvalidConfigException`  | the configuration or a submit request is inconsistent                |
| `SubmitFailedException`   | an endpoint was unreachable or rejected the submission               |

`SubmitFailedException` carries the `$endpoint`, the `$statusCode` (`null` for transport errors)
and the raw `$responseBody`, so you can branch on them instead of parsing the message.

## Notes and limitations

* Internationalised domains have to be supplied in punycode (`https://xn--wa-fka.pl/`).
* `Host` matching is exact: `example.com` does not match `www.example.com`. Configure the host
  that your canonical URLs actually use.
* The package does not retry on its own. A failed run leaves the URLs in the store, so the next
  scheduled run retries them.

## Quality gates

This package targets:

* PHP 8.2, 8.3, 8.4 and 8.5, verified against both lowest and highest dependency versions
* PHPStan **level 10** with strict rules
* 100% line coverage and a 100% mutation score ([Infection](https://infection.github.io/))
* PSR-12 based coding standard enforced by PHP-CS-Fixer
* `composer audit` and Dependabot for dependency hygiene

Local development:

```bash
composer install
composer ci          # cs + phpstan + test + infection
composer test        # PHPUnit only
composer infection   # requires xdebug or pcov
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for the full workflow.

## Roadmap

1. Core package (this repo).
2. [`slimad/indexnow-laravel`](https://github.com/maciej-kosiedowski/IndexNow-for-Laravel) —
   service provider, config publishing, cache/database stores, queued job, Artisan commands,
   scheduler binding, model observer.

## License

MIT — see [LICENSE](LICENSE).

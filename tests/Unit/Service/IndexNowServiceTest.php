<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Service\IndexNowService;
use SlimAD\IndexNow\Store\InMemoryUrlStore;
use SlimAD\IndexNow\ValueObject\Url;

final class IndexNowServiceTest extends TestCase
{
    public function testSubmitQueuesUrl(): void
    {
        $store = new InMemoryUrlStore();
        $service = new IndexNowService($store);

        $service->submit(new Url('https://example.com/a'));

        self::assertSame(1, $service->pending());
        self::assertSame(1, $store->count());
    }

    public function testSubmitManyQueuesAllUrls(): void
    {
        $store = new InMemoryUrlStore();
        $service = new IndexNowService($store);

        $service->submitMany([
            new Url('https://example.com/a'),
            new Url('https://example.com/b'),
            new Url('https://example.com/a'),
        ]);

        self::assertSame(2, $service->pending());
    }
}

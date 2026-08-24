<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Store;

use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Store\InMemoryUrlStore;
use SlimAD\IndexNow\ValueObject\Url;

final class InMemoryUrlStoreTest extends TestCase
{
    public function testAddCollectsUniqueUrls(): void
    {
        $store = new InMemoryUrlStore();

        $store->add(new Url('https://example.com/a'));
        $store->add(new Url('https://example.com/b'));
        $store->add(new Url('https://example.com/a'));

        self::assertSame(2, $store->count());
        self::assertEqualsCanonicalizing(
            ['https://example.com/a', 'https://example.com/b'],
            array_map(static fn (Url $url): string => $url->value, $store->all()),
        );
    }

    public function testRemove(): void
    {
        $store = new InMemoryUrlStore();
        $a = new Url('https://example.com/a');
        $b = new Url('https://example.com/b');

        $store->add($a);
        $store->add($b);
        $store->remove($a);

        self::assertSame(1, $store->count());
        self::assertSame([$b], $store->all());
    }

    public function testRemoveOfMissingUrlIsNoop(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));

        $store->remove(new Url('https://example.com/missing'));

        self::assertSame(1, $store->count());
    }

    public function testClear(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));
        $store->add(new Url('https://example.com/b'));

        $store->clear();

        self::assertSame(0, $store->count());
        self::assertSame([], $store->all());
    }
}

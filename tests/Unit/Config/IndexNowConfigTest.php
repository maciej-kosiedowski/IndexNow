<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Config\IndexNowConfig;
use SlimAD\IndexNow\Config\SearchEngine;
use SlimAD\IndexNow\Exception\InvalidConfigException;
use SlimAD\IndexNow\ValueObject\Host;
use SlimAD\IndexNow\ValueObject\Key;
use SlimAD\IndexNow\ValueObject\KeyLocation;

final class IndexNowConfigTest extends TestCase
{
    public function testIndexesEnginesByName(): void
    {
        $bing = SearchEngine::bing(new Key('abcdef0123456789'), KeyLocation::fromString('https://example.com/k.txt'));
        $yandex = SearchEngine::yandex(new Key('0123456789abcdef'), KeyLocation::fromString('https://example.com/k.txt'));

        $config = new IndexNowConfig(new Host('example.com'), [$bing, $yandex]);

        self::assertCount(2, $config->engines);
        self::assertSame($bing, $config->engine('bing'));
        self::assertSame($yandex, $config->engine('yandex'));
        self::assertNull($config->engine('unknown'));
    }

    public function testKeepsTheConfiguredEngineOrder(): void
    {
        $yandex = SearchEngine::yandex(new Key('0123456789abcdef'), KeyLocation::fromString('https://example.com/k.txt'));
        $bing = SearchEngine::bing(new Key('abcdef0123456789'), KeyLocation::fromString('https://example.com/k.txt'));

        $config = new IndexNowConfig(new Host('example.com'), [$yandex, $bing]);

        self::assertSame(['yandex', 'bing'], array_keys($config->engines));
    }

    public function testRejectsEmptyEngines(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('at least one search engine');

        new IndexNowConfig(new Host('example.com'), []);
    }

    public function testRejectsDuplicateEngineNames(): void
    {
        $first = SearchEngine::bing(new Key('abcdef0123456789'), KeyLocation::fromString('https://example.com/one.txt'));
        $second = SearchEngine::bing(new Key('0123456789abcdef'), KeyLocation::fromString('https://example.com/two.txt'));

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Search engine "bing" is configured more than once');

        new IndexNowConfig(new Host('example.com'), [$first, $second]);
    }
}

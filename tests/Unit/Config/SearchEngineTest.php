<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Config\SearchEngine;
use SlimAD\IndexNow\Exception\InvalidConfigException;
use SlimAD\IndexNow\Exception\InvalidUrlException;
use SlimAD\IndexNow\ValueObject\Key;
use SlimAD\IndexNow\ValueObject\KeyLocation;

final class SearchEngineTest extends TestCase
{
    public function testCustomEngine(): void
    {
        $engine = new SearchEngine(
            'custom',
            'https://example.com/indexnow',
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );

        self::assertSame('custom', $engine->name);
        self::assertSame('https://example.com/indexnow', $engine->endpoint);
        self::assertSame('abcdef0123456789', $engine->key->value);
        self::assertSame('https://example.com/abcdef0123456789.txt', $engine->keyLocation->value());
    }

    public function testRejectsInvalidEndpoint(): void
    {
        $this->expectException(InvalidUrlException::class);

        new SearchEngine(
            'custom',
            'not-a-url',
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );
    }

    public function testBingFactory(): void
    {
        $engine = SearchEngine::bing(
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );

        self::assertSame('bing', $engine->name);
        self::assertSame(SearchEngine::ENDPOINT_BING, $engine->endpoint);
    }

    public function testYandexFactory(): void
    {
        $engine = SearchEngine::yandex(
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );

        self::assertSame('yandex', $engine->name);
        self::assertSame(SearchEngine::ENDPOINT_YANDEX, $engine->endpoint);
    }

    public function testSeznamFactory(): void
    {
        $engine = SearchEngine::seznam(
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );

        self::assertSame('seznam', $engine->name);
        self::assertSame(SearchEngine::ENDPOINT_SEZNAM, $engine->endpoint);
    }

    public function testNaverFactory(): void
    {
        $engine = SearchEngine::naver(
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );

        self::assertSame('naver', $engine->name);
        self::assertSame(SearchEngine::ENDPOINT_NAVER, $engine->endpoint);
    }

    public function testYepFactory(): void
    {
        $engine = SearchEngine::yep(
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );

        self::assertSame('yep', $engine->name);
        self::assertSame(SearchEngine::ENDPOINT_YEP, $engine->endpoint);
    }

    public function testIndexNowApiFactory(): void
    {
        $engine = SearchEngine::indexNowApi(
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );

        self::assertSame('indexnow', $engine->name);
        self::assertSame(SearchEngine::ENDPOINT_INDEXNOW_API, $engine->endpoint);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyNameProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ["  \t\n "];
    }

    #[DataProvider('emptyNameProvider')]
    public function testRejectsEmptyName(string $name): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('non-empty name');

        new SearchEngine(
            $name,
            'https://example.com/indexnow',
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );
    }
}

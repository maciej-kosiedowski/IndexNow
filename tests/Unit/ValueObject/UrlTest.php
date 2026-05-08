<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Exception\InvalidUrlException;
use SlimAD\IndexNow\ValueObject\Url;

final class UrlTest extends TestCase
{
    public function testAcceptsHttpsUrl(): void
    {
        $url = new Url('https://example.com/path?q=1');

        self::assertSame('https://example.com/path?q=1', $url->value);
        self::assertSame('https://example.com/path?q=1', (string) $url);
    }

    public function testAcceptsHttpUrl(): void
    {
        $url = new Url('http://example.com');

        self::assertSame('http://example.com', $url->value);
    }

    public function testTrimsWhitespace(): void
    {
        $url = new Url("   https://example.com\n");

        self::assertSame('https://example.com', $url->value);
    }

    public function testHostReturnsLowercaseHost(): void
    {
        $url = new Url('https://Example.COM/path');

        self::assertSame('example.com', $url->host());
    }

    public function testEqualsCompareValues(): void
    {
        $a = new Url('https://example.com/a');
        $b = new Url('https://example.com/a');
        $c = new Url('https://example.com/b');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidUrlProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'no scheme' => ['example.com'];
        yield 'malformed' => ['http://'];
        yield 'with spaces' => ['https://example .com'];
    }

    #[DataProvider('invalidUrlProvider')]
    public function testRejectsInvalidUrls(string $value): void
    {
        $this->expectException(InvalidUrlException::class);

        new Url($value);
    }

    public function testRejectsUnsupportedScheme(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('ftp');

        new Url('ftp://example.com');
    }
}

<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Exception\InvalidHostException;
use SlimAD\IndexNow\ValueObject\Host;
use SlimAD\IndexNow\ValueObject\Url;

final class HostTest extends TestCase
{
    public function testAcceptsValidHostname(): void
    {
        $host = new Host('www.example.com');

        self::assertSame('www.example.com', $host->value);
        self::assertSame('www.example.com', (string) $host);
    }

    public function testNormalisesCaseAndWhitespace(): void
    {
        $host = new Host('  WWW.Example.COM  ');

        self::assertSame('www.example.com', $host->value);
    }

    public function testDropsTheFullyQualifiedTrailingDot(): void
    {
        $host = new Host('example.com.');

        self::assertSame('example.com', $host->value);
    }

    public function testMatchesUrlOnSameHost(): void
    {
        $host = new Host('example.com');

        self::assertTrue($host->matches(new Url('https://example.com/foo')));
        self::assertTrue($host->matches(new Url('https://EXAMPLE.com/foo')));
        self::assertFalse($host->matches(new Url('https://other.com/foo')));
    }

    public function testMatchesRegardlessOfTheTrailingDotOnEitherSide(): void
    {
        self::assertTrue((new Host('example.com.'))->matches(new Url('https://example.com/foo')));
        self::assertTrue((new Host('example.com'))->matches(new Url('https://example.com./foo')));
    }

    public function testDoesNotMatchSubdomains(): void
    {
        $host = new Host('example.com');

        self::assertFalse($host->matches(new Url('https://www.example.com/foo')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidHostProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'dot only' => ['.'];
        yield 'with scheme' => ['https://example.com'];
        yield 'with slash' => ['example.com/path'];
        yield 'with space' => ['example .com'];
        yield 'with underscore' => ['exa_mple.com'];
        yield 'leading dash' => ['-example.com'];
    }

    #[DataProvider('invalidHostProvider')]
    public function testRejectsInvalidHosts(string $value): void
    {
        $this->expectException(InvalidHostException::class);

        new Host($value);
    }
}

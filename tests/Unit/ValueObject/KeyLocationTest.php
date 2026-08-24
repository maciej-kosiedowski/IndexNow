<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\ValueObject;

use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Exception\InvalidUrlException;
use SlimAD\IndexNow\ValueObject\KeyLocation;
use SlimAD\IndexNow\ValueObject\Url;

final class KeyLocationTest extends TestCase
{
    public function testWrapsUrl(): void
    {
        $url = new Url('https://example.com/key.txt');

        $location = new KeyLocation($url);

        self::assertSame($url, $location->url);
        self::assertSame('https://example.com/key.txt', $location->value());
        self::assertSame('https://example.com/key.txt', (string) $location);
    }

    public function testFromStringBuildsUrl(): void
    {
        $location = KeyLocation::fromString('https://example.com/keyfile.txt');

        self::assertSame('https://example.com/keyfile.txt', $location->value());
    }

    public function testFromStringRejectsInvalidUrl(): void
    {
        $this->expectException(InvalidUrlException::class);

        KeyLocation::fromString('not-a-url');
    }
}

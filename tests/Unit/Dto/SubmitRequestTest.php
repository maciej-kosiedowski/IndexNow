<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Dto;

use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Dto\SubmitRequest;
use SlimAD\IndexNow\Exception\InvalidConfigException;
use SlimAD\IndexNow\ValueObject\Host;
use SlimAD\IndexNow\ValueObject\Key;
use SlimAD\IndexNow\ValueObject\KeyLocation;
use SlimAD\IndexNow\ValueObject\Url;

final class SubmitRequestTest extends TestCase
{
    public function testBuildsPayload(): void
    {
        $request = new SubmitRequest(
            'https://api.indexnow.org/indexnow',
            new Host('example.com'),
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
            new Url('https://example.com/a'),
            new Url('https://example.com/b'),
        );

        self::assertSame('https://api.indexnow.org/indexnow', $request->endpoint);
        self::assertSame([
            'host' => 'example.com',
            'key' => 'abcdef0123456789',
            'keyLocation' => 'https://example.com/abcdef0123456789.txt',
            'urlList' => ['https://example.com/a', 'https://example.com/b'],
        ], $request->toArray());
    }

    public function testDeduplicatesUrls(): void
    {
        $request = new SubmitRequest(
            'https://api.indexnow.org/indexnow',
            new Host('example.com'),
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
            new Url('https://example.com/a'),
            new Url('https://example.com/a'),
            new Url('https://example.com/b'),
        );

        self::assertCount(2, $request->urls);
        self::assertSame(['https://example.com/a', 'https://example.com/b'], $request->toArray()['urlList']);
    }

    public function testRejectsEmptyUrlList(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('at least one URL');

        new SubmitRequest(
            'https://api.indexnow.org/indexnow',
            new Host('example.com'),
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
        );
    }

    public function testRejectsUrlsFromDifferentHost(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('does not belong');

        new SubmitRequest(
            'https://api.indexnow.org/indexnow',
            new Host('example.com'),
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
            new Url('https://other.com/a'),
        );
    }
}

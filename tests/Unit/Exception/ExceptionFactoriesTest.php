<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SlimAD\IndexNow\Exception\InvalidConfigException;
use SlimAD\IndexNow\Exception\InvalidHostException;
use SlimAD\IndexNow\Exception\InvalidKeyException;
use SlimAD\IndexNow\Exception\InvalidUrlException;
use SlimAD\IndexNow\Exception\SubmitFailedException;

final class ExceptionFactoriesTest extends TestCase
{
    public function testInvalidUrlMessages(): void
    {
        self::assertStringContainsString('foo', InvalidUrlException::notAUrl('foo')->getMessage());
        self::assertStringContainsString('ftp', InvalidUrlException::unsupportedScheme('ftp')->getMessage());
    }

    public function testInvalidKeyMessages(): void
    {
        self::assertStringContainsString('5', InvalidKeyException::invalidLength(5)->getMessage());
        self::assertStringContainsString('letters, digits and dashes', InvalidKeyException::invalidCharacters()->getMessage());
    }

    public function testInvalidHostMessages(): void
    {
        self::assertStringContainsString('foo', InvalidHostException::notAHost('foo')->getMessage());
    }

    public function testInvalidConfigMessages(): void
    {
        self::assertStringContainsString('search engine', InvalidConfigException::emptyEngines()->getMessage());
        self::assertStringContainsString('https://example.com/x', InvalidConfigException::urlDoesNotMatchHost('https://example.com/x', 'other.com')->getMessage());
        self::assertStringContainsString('at least one URL', InvalidConfigException::emptyUrlList()->getMessage());
    }

    public function testSubmitFailedTransport(): void
    {
        $previous = new RuntimeException('connect timeout');

        $exception = SubmitFailedException::transportError('https://api.indexnow.org/indexnow', $previous);

        self::assertStringContainsString('connect timeout', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testSubmitFailedRejected(): void
    {
        $exception = SubmitFailedException::rejected('https://api.indexnow.org/indexnow', 422, 'bad key');

        self::assertStringContainsString('422', $exception->getMessage());
        self::assertStringContainsString('bad key', $exception->getMessage());
    }
}

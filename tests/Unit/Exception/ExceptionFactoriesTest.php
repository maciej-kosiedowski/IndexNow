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
        self::assertStringContainsString('non-empty name', InvalidConfigException::emptyEngineName()->getMessage());
        self::assertStringContainsString('https://example.com/x', InvalidConfigException::urlDoesNotMatchHost('https://example.com/x', 'other.com')->getMessage());
        self::assertStringContainsString('other.com', InvalidConfigException::urlDoesNotMatchHost('https://example.com/x', 'other.com')->getMessage());
        self::assertStringContainsString('at least one URL', InvalidConfigException::emptyUrlList()->getMessage());
    }

    public function testDuplicateEngineMessage(): void
    {
        self::assertSame(
            'Search engine "bing" is configured more than once; engine names must be unique.',
            InvalidConfigException::duplicateEngine('bing')->getMessage(),
        );
    }

    public function testInvalidBatchSizeMessage(): void
    {
        self::assertSame(
            'Batch size must be between 1 and 10000, 0 given.',
            InvalidConfigException::invalidBatchSize(0, 10000)->getMessage(),
        );
    }

    public function testSubmitFailedTransport(): void
    {
        $previous = new RuntimeException('connect timeout');

        $exception = SubmitFailedException::transportError('https://api.indexnow.org/indexnow', $previous);

        self::assertSame(
            'Failed to reach IndexNow endpoint "https://api.indexnow.org/indexnow": connect timeout',
            $exception->getMessage(),
        );
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(0, $exception->getCode());
        self::assertSame('https://api.indexnow.org/indexnow', $exception->endpoint);
        self::assertNull($exception->statusCode);
        self::assertNull($exception->responseBody);
    }

    public function testSubmitFailedRejected(): void
    {
        $exception = SubmitFailedException::rejected('https://api.indexnow.org/indexnow', 422, 'bad key');

        self::assertSame(
            'IndexNow endpoint "https://api.indexnow.org/indexnow" rejected the submission with status 422: bad key',
            $exception->getMessage(),
        );
        self::assertNull($exception->getPrevious());
        self::assertSame(0, $exception->getCode());
        self::assertSame('https://api.indexnow.org/indexnow', $exception->endpoint);
        self::assertSame(422, $exception->statusCode);
        self::assertSame('bad key', $exception->responseBody);
    }

    public function testRejectedCollapsesWhitespaceInTheMessage(): void
    {
        $exception = SubmitFailedException::rejected('https://example.com', 500, "  <html>\n\n\t<body>boom</body>\n</html>  ");

        self::assertStringEndsWith(': <html> <body>boom</body> </html>', $exception->getMessage());
        self::assertSame("  <html>\n\n\t<body>boom</body>\n</html>  ", $exception->responseBody, 'the untouched body stays available');
    }

    public function testRejectedDescribesAnEmptyBody(): void
    {
        self::assertStringEndsWith(
            ': <empty response body>',
            SubmitFailedException::rejected('https://example.com', 500, '')->getMessage(),
        );
        self::assertStringEndsWith(
            ': <empty response body>',
            SubmitFailedException::rejected('https://example.com', 500, "  \n\t ")->getMessage(),
        );
    }

    public function testRejectedKeepsBodiesUpToTheSnippetLimit(): void
    {
        $body = str_repeat('a', SubmitFailedException::MAX_BODY_SNIPPET_LENGTH);

        $exception = SubmitFailedException::rejected('https://example.com', 500, $body);

        self::assertStringEndsWith(': ' . $body, $exception->getMessage());
        self::assertStringNotContainsString('...', $exception->getMessage());
    }

    public function testRejectedTruncatesLongerBodiesFromTheStart(): void
    {
        $body = 'X' . str_repeat('a', SubmitFailedException::MAX_BODY_SNIPPET_LENGTH);

        $exception = SubmitFailedException::rejected('https://example.com', 500, $body);

        self::assertStringEndsWith(
            ': X' . str_repeat('a', SubmitFailedException::MAX_BODY_SNIPPET_LENGTH - 1) . '...',
            $exception->getMessage(),
        );
        self::assertSame($body, $exception->responseBody);
    }
}

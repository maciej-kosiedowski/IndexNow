<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Job;

use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Exception\SubmitFailedException;
use SlimAD\IndexNow\Job\SubmitJobResult;

final class SubmitJobResultTest extends TestCase
{
    public function testIdle(): void
    {
        $result = SubmitJobResult::idle();

        self::assertSame(0, $result->submittedUrls);
        self::assertSame(0, $result->discardedUrls);
        self::assertSame([], $result->failures);
        self::assertTrue($result->isSuccess());
        self::assertFalse($result->hasFailures());
    }

    public function testDiscardedUrlsDefaultToZero(): void
    {
        $result = new SubmitJobResult(3, []);

        self::assertSame(0, $result->discardedUrls);
    }

    public function testWithFailures(): void
    {
        $failure = SubmitFailedException::rejected('https://example.com', 500, 'boom');

        $result = new SubmitJobResult(3, [$failure], 2);

        self::assertSame(3, $result->submittedUrls);
        self::assertSame(2, $result->discardedUrls);
        self::assertSame([$failure], $result->failures);
        self::assertFalse($result->isSuccess());
        self::assertTrue($result->hasFailures());
    }
}

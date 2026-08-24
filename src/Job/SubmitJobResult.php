<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Job;

use SlimAD\IndexNow\Exception\SubmitFailedException;

final class SubmitJobResult
{
    /**
     * @param list<SubmitFailedException> $failures
     */
    public function __construct(
        public readonly int $submittedUrls,
        public readonly array $failures,
    ) {
    }

    public static function idle(): self
    {
        return new self(0, []);
    }

    public function isSuccess(): bool
    {
        return $this->failures === [];
    }

    public function hasFailures(): bool
    {
        return $this->failures !== [];
    }
}

<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Job;

use SlimAD\IndexNow\Exception\SubmitFailedException;

final class SubmitJobResult
{
    /**
     * @param list<SubmitFailedException> $failures one entry per engine that rejected the batch or was unreachable
     * @param int $discardedUrls number of queued URLs dropped because they do not belong to the configured host
     */
    public function __construct(
        public readonly int $submittedUrls,
        public readonly array $failures,
        public readonly int $discardedUrls = 0,
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

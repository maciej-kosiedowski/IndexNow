<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Exception;

use Throwable;

final class SubmitFailedException extends IndexNowException
{
    public static function transportError(string $endpoint, Throwable $previous): self
    {
        return new self(
            \sprintf('Failed to reach IndexNow endpoint "%s": %s', $endpoint, $previous->getMessage()),
            0,
            $previous,
        );
    }

    public static function rejected(string $endpoint, int $statusCode, string $body): self
    {
        return new self(\sprintf(
            'IndexNow endpoint "%s" rejected the submission with status %d: %s',
            $endpoint,
            $statusCode,
            $body,
        ));
    }
}

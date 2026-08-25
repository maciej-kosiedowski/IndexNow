<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Exception;

use Throwable;

final class SubmitFailedException extends IndexNowException
{
    /**
     * Upper bound for the response body echoed into the exception message.
     *
     * Endpoints occasionally answer with a full HTML error page; the untruncated
     * body would end up in every log line that renders this exception.
     */
    public const MAX_BODY_SNIPPET_LENGTH = 500;

    private function __construct(
        string $message,
        public readonly string $endpoint,
        public readonly ?int $statusCode,
        public readonly ?string $responseBody,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function transportError(string $endpoint, Throwable $previous): self
    {
        return new self(
            \sprintf('Failed to reach IndexNow endpoint "%s": %s', $endpoint, $previous->getMessage()),
            $endpoint,
            null,
            null,
            $previous,
        );
    }

    public static function rejected(string $endpoint, int $statusCode, string $body): self
    {
        return new self(
            \sprintf(
                'IndexNow endpoint "%s" rejected the submission with status %d: %s',
                $endpoint,
                $statusCode,
                self::snippet($body),
            ),
            $endpoint,
            $statusCode,
            $body,
        );
    }

    private static function snippet(string $body): string
    {
        $normalised = trim((string) preg_replace('/\s+/', ' ', $body));

        if ($normalised === '') {
            return '<empty response body>';
        }

        if (\strlen($normalised) <= self::MAX_BODY_SNIPPET_LENGTH) {
            return $normalised;
        }

        return substr($normalised, 0, self::MAX_BODY_SNIPPET_LENGTH) . '...';
    }
}

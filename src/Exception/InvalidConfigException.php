<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Exception;

final class InvalidConfigException extends IndexNowException
{
    public static function emptyEngines(): self
    {
        return new self('IndexNow configuration must declare at least one search engine.');
    }

    public static function emptyEngineName(): self
    {
        return new self('A search engine must have a non-empty name.');
    }

    public static function duplicateEngine(string $name): self
    {
        return new self(\sprintf(
            'Search engine "%s" is configured more than once; engine names must be unique.',
            $name,
        ));
    }

    public static function urlDoesNotMatchHost(string $url, string $host): self
    {
        return new self(\sprintf(
            'URL "%s" does not belong to configured host "%s".',
            $url,
            $host,
        ));
    }

    public static function emptyUrlList(): self
    {
        return new self('Submit request must contain at least one URL.');
    }

    public static function invalidBatchSize(int $batchSize, int $maximum): self
    {
        return new self(\sprintf(
            'Batch size must be between 1 and %d, %d given.',
            $maximum,
            $batchSize,
        ));
    }
}

<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Exception;

final class InvalidConfigException extends IndexNowException
{
    public static function emptyEngines(): self
    {
        return new self('IndexNow configuration must declare at least one search engine.');
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
}

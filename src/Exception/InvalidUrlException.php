<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Exception;

final class InvalidUrlException extends IndexNowException
{
    public static function notAUrl(string $value): self
    {
        return new self(\sprintf('Value "%s" is not a valid http/https URL.', $value));
    }

    public static function unsupportedScheme(string $scheme): self
    {
        return new self(\sprintf('URL scheme "%s" is not supported, only http and https are allowed.', $scheme));
    }
}

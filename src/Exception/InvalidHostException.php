<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Exception;

final class InvalidHostException extends IndexNowException
{
    public static function notAHost(string $value): self
    {
        return new self(\sprintf('Value "%s" is not a valid host name.', $value));
    }
}

<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\ValueObject;

use SlimAD\IndexNow\Exception\InvalidHostException;

final class Host
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = strtolower(trim($value));

        if ($trimmed === '' || filter_var($trimmed, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw InvalidHostException::notAHost($value);
        }

        $this->value = $trimmed;
    }

    public function matches(Url $url): bool
    {
        return $url->host() === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

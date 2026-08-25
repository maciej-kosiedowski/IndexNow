<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\ValueObject;

use SlimAD\IndexNow\Exception\InvalidHostException;

/**
 * The canonical host that owns every URL submitted through this package.
 *
 * Values are normalised to lower case and stripped of the (legal but rarely
 * used) fully qualified trailing dot so that `example.com.` and `example.com`
 * are treated as the same host.
 */
final class Host
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $normalised = rtrim(strtolower(trim($value)), '.');

        if ($normalised === '' || filter_var($normalised, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw InvalidHostException::notAHost($value);
        }

        $this->value = $normalised;
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

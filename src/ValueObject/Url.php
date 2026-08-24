<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\ValueObject;

use SlimAD\IndexNow\Exception\InvalidUrlException;

final class Url
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if (preg_match('#^([A-Za-z][A-Za-z0-9+\-.]*)://#', $trimmed, $matches) !== 1) {
            throw InvalidUrlException::notAUrl($value);
        }

        $scheme = strtolower($matches[1]);

        if ($scheme !== 'http' && $scheme !== 'https') {
            throw InvalidUrlException::unsupportedScheme($scheme);
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            throw InvalidUrlException::notAUrl($value);
        }

        $this->value = $trimmed;
    }

    public function host(): string
    {
        $host = parse_url($this->value, PHP_URL_HOST);
        \assert(\is_string($host) && $host !== '');

        return strtolower($host);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

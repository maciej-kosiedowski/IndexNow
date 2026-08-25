<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\ValueObject;

use SlimAD\IndexNow\Exception\InvalidUrlException;

/**
 * An absolute http/https URL.
 *
 * Internationalised domain names have to be supplied in their punycode form
 * (`https://xn--wa-fka.pl/`); IndexNow endpoints reject non-ASCII hosts too.
 */
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

    /**
     * The lower-cased host of this URL, without the optional trailing dot.
     */
    public function host(): string
    {
        /** @var string $host the constructor already guaranteed that this URL has a host */
        $host = parse_url($this->value, PHP_URL_HOST);

        return rtrim(strtolower($host), '.');
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

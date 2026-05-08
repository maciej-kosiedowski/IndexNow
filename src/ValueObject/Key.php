<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\ValueObject;

use SlimAD\IndexNow\Exception\InvalidKeyException;

final class Key
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 128;
    private const PATTERN = '/^[A-Za-z0-9\-]+$/';

    public readonly string $value;

    public function __construct(string $value)
    {
        $length = \strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw InvalidKeyException::invalidLength($length);
        }

        if (preg_match(self::PATTERN, $value) !== 1) {
            throw InvalidKeyException::invalidCharacters();
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Exception;

final class InvalidKeyException extends IndexNowException
{
    public static function invalidLength(int $length): self
    {
        return new self(\sprintf(
            'IndexNow key must be between 8 and 128 characters long, %d given.',
            $length,
        ));
    }

    public static function invalidCharacters(): self
    {
        return new self(
            'IndexNow key may only contain ASCII letters, digits and dashes.',
        );
    }
}

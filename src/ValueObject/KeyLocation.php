<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\ValueObject;

final class KeyLocation
{
    public readonly Url $url;

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    public static function fromString(string $url): self
    {
        return new self(new Url($url));
    }

    public function value(): string
    {
        return $this->url->value;
    }

    public function __toString(): string
    {
        return $this->url->value;
    }
}

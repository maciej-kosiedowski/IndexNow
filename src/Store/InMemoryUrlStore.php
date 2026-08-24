<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Store;

use SlimAD\IndexNow\ValueObject\Url;

final class InMemoryUrlStore implements UrlStore
{
    /** @var array<string, Url> */
    private array $urls = [];

    public function add(Url $url): void
    {
        $this->urls[$url->value] = $url;
    }

    /**
     * @return list<Url>
     */
    public function all(): array
    {
        return array_values($this->urls);
    }

    public function remove(Url $url): void
    {
        unset($this->urls[$url->value]);
    }

    public function clear(): void
    {
        $this->urls = [];
    }

    public function count(): int
    {
        return \count($this->urls);
    }
}

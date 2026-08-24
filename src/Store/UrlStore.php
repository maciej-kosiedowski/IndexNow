<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Store;

use SlimAD\IndexNow\ValueObject\Url;

interface UrlStore
{
    public function add(Url $url): void;

    /**
     * @return list<Url>
     */
    public function all(): array;

    public function remove(Url $url): void;

    public function clear(): void;

    public function count(): int;
}

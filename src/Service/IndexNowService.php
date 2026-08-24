<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Service;

use SlimAD\IndexNow\Store\UrlStore;
use SlimAD\IndexNow\ValueObject\Url;

final class IndexNowService
{
    public function __construct(
        private readonly UrlStore $store,
    ) {
    }

    public function submit(Url $url): void
    {
        $this->store->add($url);
    }

    /**
     * @param iterable<Url> $urls
     */
    public function submitMany(iterable $urls): void
    {
        foreach ($urls as $url) {
            $this->store->add($url);
        }
    }

    public function pending(): int
    {
        return $this->store->count();
    }
}

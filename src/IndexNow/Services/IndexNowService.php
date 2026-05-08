<?php

namespace SlimAD\IndexNow\Services;

use Illuminate\Support\Facades\Cache;

class IndexNowService
{
    private string $cacheKey;

    public function __construct()
    {
        $this->cacheKey = config('indexnow.cache_key', 'indexnow_pending_urls');
    }

    /**
     * Add a URL to the pending list in cache.
     *
     * @param string $url
     * @return void
     */
    public function submit(string $url): void
    {
        $urls = Cache::get($this->cacheKey, []);

        if (!in_array($url, $urls)) {
            $urls[] = $url;
            Cache::put($this->cacheKey, $urls);
        }
    }
}

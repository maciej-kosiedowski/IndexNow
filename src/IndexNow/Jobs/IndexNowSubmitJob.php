<?php

namespace SlimAD\IndexNow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SlimAD\IndexNow\Contracts\IndexNowClient;
use SlimAD\IndexNow\DTO\IndexNowSubmitDTO;

class IndexNowSubmitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(IndexNowClient $client): void
    {
        $cacheKey = config('indexnow.cache_key', 'indexnow_pending_urls');

        // Use pull to get and remove the items atomically to avoid race conditions
        // where new URLs are added while the job is processing the current batch
        $urls = Cache::pull($cacheKey, []);

        if (empty($urls)) {
            return;
        }

        $keys = config('indexnow.keys', []);

        if (empty($keys)) {
            Log::warning('IndexNow: No API keys configured.');
            // Put the URLs back into cache since we couldn't process them
            $this->requeueUrls($cacheKey, $urls);
            return;
        }

        $failedUrls = [];

        foreach ($urls as $url) {
            $dto = new IndexNowSubmitDTO($url);
            $successForAllEngines = true;

            foreach ($keys as $engine => $key) {
                if (empty($key)) {
                    continue;
                }

                $success = $client->submit($dto, $key);

                if (!$success) {
                    $successForAllEngines = false;
                    Log::error("IndexNow: Failed to submit {$url} to {$engine}.");
                }
            }

            if (!$successForAllEngines) {
                $failedUrls[] = $url;
            }
        }

        if (!empty($failedUrls)) {
            $this->requeueUrls($cacheKey, $failedUrls);
        }
    }

    private function requeueUrls(string $cacheKey, array $urlsToRequeue): void
    {
        // We need to fetch the current list and append the failed ones
        // in case new URLs were added while the job was running
        $currentUrls = Cache::get($cacheKey, []);
        $mergedUrls = array_unique(array_merge($currentUrls, $urlsToRequeue));
        Cache::put($cacheKey, $mergedUrls);
    }
}

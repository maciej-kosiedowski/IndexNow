<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Job;

use SlimAD\IndexNow\Client\IndexNowClient;
use SlimAD\IndexNow\Config\IndexNowConfig;
use SlimAD\IndexNow\Config\SearchEngine;
use SlimAD\IndexNow\Dto\SubmitRequest;
use SlimAD\IndexNow\Exception\InvalidConfigException;
use SlimAD\IndexNow\Exception\SubmitFailedException;
use SlimAD\IndexNow\Store\UrlStore;
use SlimAD\IndexNow\ValueObject\Url;

final class SubmitJob
{
    /**
     * The IndexNow specification caps a single submission at 10 000 URLs.
     *
     * @see https://www.indexnow.org/documentation
     */
    public const MAX_URLS_PER_REQUEST = 10000;

    private readonly int $batchSize;

    public function __construct(
        private readonly UrlStore $store,
        private readonly IndexNowClient $client,
        private readonly IndexNowConfig $config,
        int $batchSize = self::MAX_URLS_PER_REQUEST,
    ) {
        if ($batchSize < 1 || $batchSize > self::MAX_URLS_PER_REQUEST) {
            throw InvalidConfigException::invalidBatchSize($batchSize, self::MAX_URLS_PER_REQUEST);
        }

        $this->batchSize = $batchSize;
    }

    /**
     * Drains the store: forwards every queued URL to every configured search
     * engine and removes URLs that were accepted by all engines.
     *
     * URLs that do not belong to the configured host are dropped instead of
     * being submitted — otherwise a single foreign URL would make every future
     * run fail and would never leave the queue.
     */
    public function run(): SubmitJobResult
    {
        $submittable = [];
        $discarded = 0;

        foreach ($this->store->all() as $url) {
            if ($this->config->host->matches($url)) {
                $submittable[] = $url;

                continue;
            }

            ++$discarded;
            $this->store->remove($url);
        }

        /** @var list<SubmitFailedException> $failures */
        $failures = [];

        foreach (array_chunk($submittable, $this->batchSize) as $batch) {
            foreach ($this->config->engines as $engine) {
                try {
                    $this->dispatch($engine, $batch);
                } catch (SubmitFailedException $exception) {
                    $failures[] = $exception;
                }
            }
        }

        if ($failures === []) {
            foreach ($submittable as $url) {
                $this->store->remove($url);
            }
        }

        return new SubmitJobResult(\count($submittable), $failures, $discarded);
    }

    /**
     * @param list<Url> $urls
     */
    private function dispatch(SearchEngine $engine, array $urls): void
    {
        $request = new SubmitRequest(
            $engine->endpoint,
            $this->config->host,
            $engine->key,
            $engine->keyLocation,
            ...$urls,
        );

        $this->client->submit($request);
    }
}

<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Job;

use SlimAD\IndexNow\Client\IndexNowClient;
use SlimAD\IndexNow\Config\IndexNowConfig;
use SlimAD\IndexNow\Config\SearchEngine;
use SlimAD\IndexNow\Dto\SubmitRequest;
use SlimAD\IndexNow\Exception\SubmitFailedException;
use SlimAD\IndexNow\Store\UrlStore;
use SlimAD\IndexNow\ValueObject\Url;

final class SubmitJob
{
    public function __construct(
        private readonly UrlStore $store,
        private readonly IndexNowClient $client,
        private readonly IndexNowConfig $config,
    ) {
    }

    /**
     * Drains the store: forwards every queued URL to every configured search
     * engine and removes URLs that were accepted by all engines.
     */
    public function run(): SubmitJobResult
    {
        $queued = $this->store->all();

        if ($queued === []) {
            return SubmitJobResult::idle();
        }

        /** @var list<SubmitFailedException> $failures */
        $failures = [];

        foreach ($this->config->engines as $engine) {
            try {
                $this->dispatch($engine, $queued);
            } catch (SubmitFailedException $exception) {
                $failures[] = $exception;
            }
        }

        if ($failures === []) {
            foreach ($queued as $url) {
                $this->store->remove($url);
            }
        }

        return new SubmitJobResult(\count($queued), $failures);
    }

    /**
     * @param non-empty-list<Url> $urls
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

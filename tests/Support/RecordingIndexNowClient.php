<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Support;

use SlimAD\IndexNow\Client\IndexNowClient;
use SlimAD\IndexNow\Dto\SubmitRequest;
use SlimAD\IndexNow\Exception\SubmitFailedException;

final class RecordingIndexNowClient implements IndexNowClient
{
    /** @var list<SubmitRequest> */
    public array $requests = [];

    /** @var list<string> */
    private readonly array $failingEndpoints;

    public function __construct(string ...$failingEndpoints)
    {
        $this->failingEndpoints = array_values($failingEndpoints);
    }

    public function submit(SubmitRequest $request): void
    {
        $this->requests[] = $request;

        if (\in_array($request->endpoint, $this->failingEndpoints, true)) {
            throw SubmitFailedException::rejected($request->endpoint, 500, 'boom');
        }
    }

    /**
     * @return list<array{endpoint: string, urls: list<string>}>
     */
    public function submissions(): array
    {
        return array_map(
            static fn (SubmitRequest $request): array => [
                'endpoint' => $request->endpoint,
                'urls' => $request->toArray()['urlList'],
            ],
            $this->requests,
        );
    }
}

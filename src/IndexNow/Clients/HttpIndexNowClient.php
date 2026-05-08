<?php

namespace SlimAD\IndexNow\Clients;

use Illuminate\Support\Facades\Http;
use SlimAD\IndexNow\Contracts\IndexNowClient;
use SlimAD\IndexNow\DTO\IndexNowSubmitDTO;

class HttpIndexNowClient implements IndexNowClient
{
    private string $endpoint = 'https://api.indexnow.org/indexnow';

    public function submit(IndexNowSubmitDTO $dto, string $key): bool
    {
        $response = Http::get($this->endpoint, [
            'url' => $dto->getUrl(),
            'key' => $key,
        ]);

        return $response->successful();
    }
}

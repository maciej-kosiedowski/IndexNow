<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Client;

use SlimAD\IndexNow\Dto\SubmitRequest;
use SlimAD\IndexNow\Exception\SubmitFailedException;

interface IndexNowClient
{
    /**
     * Submits the given URLs to the IndexNow endpoint.
     *
     * @throws SubmitFailedException when transport fails or the endpoint rejects the request.
     */
    public function submit(SubmitRequest $request): void;
}

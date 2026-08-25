<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Client;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SlimAD\IndexNow\Dto\SubmitRequest;
use SlimAD\IndexNow\Exception\SubmitFailedException;

final class HttpIndexNowClient implements IndexNowClient
{
    public const DEFAULT_USER_AGENT = 'indexnow-php';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $userAgent = self::DEFAULT_USER_AGENT,
    ) {
    }

    public function submit(SubmitRequest $request): void
    {
        $body = json_encode($request->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $httpRequest = $this->requestFactory
            ->createRequest('POST', $request->endpoint)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', $this->userAgent)
            ->withBody($this->streamFactory->createStream($body));

        try {
            $response = $this->httpClient->sendRequest($httpRequest);
        } catch (ClientExceptionInterface $exception) {
            throw SubmitFailedException::transportError($request->endpoint, $exception);
        }

        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return;
        }

        throw SubmitFailedException::rejected(
            $request->endpoint,
            $status,
            (string) $response->getBody(),
        );
    }
}

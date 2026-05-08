<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class FakeHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var list<ResponseInterface|ClientExceptionInterface> */
    private array $responses;

    /**
     * @param list<ResponseInterface|ClientExceptionInterface> $responses
     */
    public function __construct(array $responses = [])
    {
        $this->responses = $responses === [] ? [new Response(202)] : $responses;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $next = array_shift($this->responses);

        if ($next === null) {
            throw new RuntimeException('FakeHttpClient ran out of programmed responses.');
        }

        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }
}

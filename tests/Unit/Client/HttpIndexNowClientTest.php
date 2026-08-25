<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Client;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Client\HttpIndexNowClient;
use SlimAD\IndexNow\Dto\SubmitRequest;
use SlimAD\IndexNow\Exception\SubmitFailedException;
use SlimAD\IndexNow\Tests\Support\FakeHttpClient;
use SlimAD\IndexNow\Tests\Support\TransportException;
use SlimAD\IndexNow\ValueObject\Host;
use SlimAD\IndexNow\ValueObject\Key;
use SlimAD\IndexNow\ValueObject\KeyLocation;
use SlimAD\IndexNow\ValueObject\Url;

final class HttpIndexNowClientTest extends TestCase
{
    private function makeRequest(): SubmitRequest
    {
        return new SubmitRequest(
            'https://api.indexnow.org/indexnow',
            new Host('example.com'),
            new Key('abcdef0123456789'),
            KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
            new Url('https://example.com/a'),
            new Url('https://example.com/b'),
        );
    }

    public function testSendsPostWithJsonBody(): void
    {
        $http = new FakeHttpClient([new Response(200)]);
        $factory = new Psr17Factory();
        $client = new HttpIndexNowClient($http, $factory, $factory);

        $client->submit($this->makeRequest());

        self::assertCount(1, $http->requests);
        $sent = $http->requests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('https://api.indexnow.org/indexnow', (string) $sent->getUri());
        self::assertSame('application/json; charset=utf-8', $sent->getHeaderLine('Content-Type'));
        self::assertSame('application/json', $sent->getHeaderLine('Accept'));
        self::assertSame(HttpIndexNowClient::DEFAULT_USER_AGENT, $sent->getHeaderLine('User-Agent'));

        self::assertSame(
            '{"host":"example.com","key":"abcdef0123456789","keyLocation":"https://example.com/abcdef0123456789.txt",'
            . '"urlList":["https://example.com/a","https://example.com/b"]}',
            (string) $sent->getBody(),
            'slashes must not be escaped so the payload stays readable',
        );
    }

    public function testUsesTheConfiguredUserAgent(): void
    {
        $http = new FakeHttpClient([new Response(200)]);
        $factory = new Psr17Factory();
        $client = new HttpIndexNowClient($http, $factory, $factory, 'acme-shop/2.1');

        $client->submit($this->makeRequest());

        self::assertSame('acme-shop/2.1', $http->requests[0]->getHeaderLine('User-Agent'));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function successStatusProvider(): iterable
    {
        yield '200 OK' => [200];
        yield '202 Accepted' => [202];
        yield '299 boundary' => [299];
    }

    #[DataProvider('successStatusProvider')]
    public function testAcceptsAny2xxStatus(int $status): void
    {
        $http = new FakeHttpClient([new Response($status)]);
        $factory = new Psr17Factory();
        $client = new HttpIndexNowClient($http, $factory, $factory);

        $client->submit($this->makeRequest());

        self::assertCount(1, $http->requests);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function failureStatusProvider(): iterable
    {
        yield '199 boundary' => [199];
        yield '300 boundary' => [300];
        yield '400 bad request' => [400];
        yield '403 forbidden' => [403];
        yield '422 invalid key' => [422];
        yield '500 server error' => [500];
    }

    #[DataProvider('failureStatusProvider')]
    public function testThrowsOnNon2xxResponses(int $status): void
    {
        $http = new FakeHttpClient([new Response($status, [], 'rejected body')]);
        $factory = new Psr17Factory();
        $client = new HttpIndexNowClient($http, $factory, $factory);

        try {
            $client->submit($this->makeRequest());

            self::fail('A non 2xx response must be reported as a failed submission.');
        } catch (SubmitFailedException $exception) {
            self::assertSame($status, $exception->statusCode);
            self::assertSame('rejected body', $exception->responseBody);
            self::assertSame('https://api.indexnow.org/indexnow', $exception->endpoint);
            self::assertStringContainsString((string) $status, $exception->getMessage());
        }
    }

    public function testWrapsTransportError(): void
    {
        $http = new FakeHttpClient([new TransportException('connection refused')]);
        $factory = new Psr17Factory();
        $client = new HttpIndexNowClient($http, $factory, $factory);

        try {
            $client->submit($this->makeRequest());

            self::fail('A transport error must be reported as a failed submission.');
        } catch (SubmitFailedException $exception) {
            self::assertStringContainsString('connection refused', $exception->getMessage());
            self::assertSame('https://api.indexnow.org/indexnow', $exception->endpoint);
            self::assertNull($exception->statusCode);
            self::assertNull($exception->responseBody);
        }
    }
}

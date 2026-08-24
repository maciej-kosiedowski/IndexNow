<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Job;

use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Client\IndexNowClient;
use SlimAD\IndexNow\Config\IndexNowConfig;
use SlimAD\IndexNow\Config\SearchEngine;
use SlimAD\IndexNow\Dto\SubmitRequest;
use SlimAD\IndexNow\Exception\SubmitFailedException;
use SlimAD\IndexNow\Job\SubmitJob;
use SlimAD\IndexNow\Store\InMemoryUrlStore;
use SlimAD\IndexNow\ValueObject\Host;
use SlimAD\IndexNow\ValueObject\Key;
use SlimAD\IndexNow\ValueObject\KeyLocation;
use SlimAD\IndexNow\ValueObject\Url;

final class SubmitJobTest extends TestCase
{
    private function makeConfig(): IndexNowConfig
    {
        return new IndexNowConfig(
            new Host('example.com'),
            [
                SearchEngine::bing(
                    new Key('abcdef0123456789'),
                    KeyLocation::fromString('https://example.com/abcdef0123456789.txt'),
                ),
                SearchEngine::yandex(
                    new Key('0123456789abcdef'),
                    KeyLocation::fromString('https://example.com/0123456789abcdef.txt'),
                ),
            ],
        );
    }

    public function testReturnsIdleWhenStoreEmpty(): void
    {
        $store = new InMemoryUrlStore();
        $client = $this->createMock(IndexNowClient::class);
        $client->expects(self::never())->method('submit');

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $result = $job->run();

        self::assertSame(0, $result->submittedUrls);
        self::assertTrue($result->isSuccess());
        self::assertFalse($result->hasFailures());
    }

    public function testFlushesStoreToEveryEngineOnSuccess(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));
        $store->add(new Url('https://example.com/b'));

        /** @var list<SubmitRequest> $captured */
        $captured = [];
        $client = $this->createMock(IndexNowClient::class);
        $client->expects(self::exactly(2))
            ->method('submit')
            ->willReturnCallback(static function (SubmitRequest $request) use (&$captured): void {
                $captured[] = $request;
            });

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $result = $job->run();

        self::assertSame(2, $result->submittedUrls);
        self::assertTrue($result->isSuccess());
        self::assertSame(0, $store->count(), 'queue should be drained on success');

        self::assertCount(2, $captured);
        self::assertSame(SearchEngine::ENDPOINT_BING, $captured[0]->endpoint);
        self::assertSame(SearchEngine::ENDPOINT_YANDEX, $captured[1]->endpoint);
        foreach ($captured as $request) {
            self::assertSame(['https://example.com/a', 'https://example.com/b'], $request->toArray()['urlList']);
        }
    }

    public function testKeepsUrlsWhenAnyEngineFails(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));

        $client = $this->createMock(IndexNowClient::class);
        $client->expects(self::exactly(2))
            ->method('submit')
            ->willReturnOnConsecutiveCalls(
                self::throwException(SubmitFailedException::rejected('https://www.bing.com/indexnow', 500, 'boom')),
                null,
            );

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $result = $job->run();

        self::assertSame(1, $result->submittedUrls);
        self::assertFalse($result->isSuccess());
        self::assertTrue($result->hasFailures());
        self::assertCount(1, $result->failures);
        self::assertSame(1, $store->count(), 'queue is preserved on partial failure');
    }
}

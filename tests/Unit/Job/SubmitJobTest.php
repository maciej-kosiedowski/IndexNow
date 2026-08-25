<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\Job;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Config\IndexNowConfig;
use SlimAD\IndexNow\Config\SearchEngine;
use SlimAD\IndexNow\Exception\InvalidConfigException;
use SlimAD\IndexNow\Job\SubmitJob;
use SlimAD\IndexNow\Store\InMemoryUrlStore;
use SlimAD\IndexNow\Tests\Support\RecordingIndexNowClient;
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
        $client = new RecordingIndexNowClient();

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $result = $job->run();

        self::assertSame([], $client->requests);
        self::assertSame(0, $result->submittedUrls);
        self::assertSame(0, $result->discardedUrls);
        self::assertTrue($result->isSuccess());
        self::assertFalse($result->hasFailures());
    }

    public function testFlushesStoreToEveryEngineOnSuccess(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));
        $store->add(new Url('https://example.com/b'));

        $client = new RecordingIndexNowClient();

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $result = $job->run();

        self::assertSame(2, $result->submittedUrls);
        self::assertSame(0, $result->discardedUrls);
        self::assertTrue($result->isSuccess());
        self::assertSame(0, $store->count(), 'queue should be drained on success');

        self::assertSame([
            ['endpoint' => SearchEngine::ENDPOINT_BING, 'urls' => ['https://example.com/a', 'https://example.com/b']],
            ['endpoint' => SearchEngine::ENDPOINT_YANDEX, 'urls' => ['https://example.com/a', 'https://example.com/b']],
        ], $client->submissions());
    }

    public function testKeepsUrlsWhenAnyEngineFails(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));

        $client = new RecordingIndexNowClient(SearchEngine::ENDPOINT_BING);

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $result = $job->run();

        self::assertCount(2, $client->requests, 'a failing engine must not stop the remaining engines');
        self::assertSame(1, $result->submittedUrls);
        self::assertFalse($result->isSuccess());
        self::assertTrue($result->hasFailures());
        self::assertCount(1, $result->failures);
        self::assertSame(SearchEngine::ENDPOINT_BING, $result->failures[0]->endpoint);
        self::assertSame(1, $store->count(), 'queue is preserved on partial failure');
    }

    public function testDiscardsQueuedUrlsThatDoNotBelongToTheConfiguredHost(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));
        $store->add(new Url('https://other.example/b'));
        $store->add(new Url('https://third.example/c'));

        $client = new RecordingIndexNowClient();

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $result = $job->run();

        self::assertSame(1, $result->submittedUrls);
        self::assertSame(2, $result->discardedUrls);
        self::assertTrue($result->isSuccess());
        self::assertSame(0, $store->count(), 'foreign URLs must not stay in the queue forever');

        self::assertSame([
            ['endpoint' => SearchEngine::ENDPOINT_BING, 'urls' => ['https://example.com/a']],
            ['endpoint' => SearchEngine::ENDPOINT_YANDEX, 'urls' => ['https://example.com/a']],
        ], $client->submissions());
    }

    public function testDiscardsForeignUrlsWithoutContactingAnyEngine(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://other.example/b'));

        $client = new RecordingIndexNowClient();

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $result = $job->run();

        self::assertSame([], $client->requests);
        self::assertSame(0, $result->submittedUrls);
        self::assertSame(1, $result->discardedUrls);
        self::assertTrue($result->isSuccess());
        self::assertSame(0, $store->count());
    }

    public function testKeepsForeignUrlsOutOfTheQueueEvenWhenSubmissionFails(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));
        $store->add(new Url('https://other.example/b'));

        $client = new RecordingIndexNowClient(SearchEngine::ENDPOINT_BING, SearchEngine::ENDPOINT_YANDEX);

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $result = $job->run();

        self::assertSame(1, $result->submittedUrls);
        self::assertSame(1, $result->discardedUrls);
        self::assertCount(2, $result->failures);
        self::assertSame(1, $store->count(), 'only the submittable URL is retried');
        self::assertSame('https://example.com/a', $store->all()[0]->value);
    }

    public function testSplitsLargeQueuesIntoBatches(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));
        $store->add(new Url('https://example.com/b'));
        $store->add(new Url('https://example.com/c'));

        $client = new RecordingIndexNowClient();

        $job = new SubmitJob($store, $client, $this->makeConfig(), 2);

        $result = $job->run();

        self::assertSame(3, $result->submittedUrls);
        self::assertTrue($result->isSuccess());
        self::assertSame(0, $store->count());

        self::assertSame([
            ['endpoint' => SearchEngine::ENDPOINT_BING, 'urls' => ['https://example.com/a', 'https://example.com/b']],
            ['endpoint' => SearchEngine::ENDPOINT_YANDEX, 'urls' => ['https://example.com/a', 'https://example.com/b']],
            ['endpoint' => SearchEngine::ENDPOINT_BING, 'urls' => ['https://example.com/c']],
            ['endpoint' => SearchEngine::ENDPOINT_YANDEX, 'urls' => ['https://example.com/c']],
        ], $client->submissions());
    }

    public function testSendsASingleBatchByDefault(): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));
        $store->add(new Url('https://example.com/b'));
        $store->add(new Url('https://example.com/c'));

        $client = new RecordingIndexNowClient();

        $job = new SubmitJob($store, $client, $this->makeConfig());

        $job->run();

        self::assertCount(2, $client->requests, 'one request per engine');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function acceptedBatchSizeProvider(): iterable
    {
        yield 'minimum' => [1];
        yield 'maximum' => [SubmitJob::MAX_URLS_PER_REQUEST];
    }

    #[DataProvider('acceptedBatchSizeProvider')]
    public function testAcceptsBatchSizesWithinTheProtocolLimit(int $batchSize): void
    {
        $store = new InMemoryUrlStore();
        $store->add(new Url('https://example.com/a'));

        $client = new RecordingIndexNowClient();

        $job = new SubmitJob($store, $client, $this->makeConfig(), $batchSize);

        self::assertSame(1, $job->run()->submittedUrls);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function rejectedBatchSizeProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'above the protocol limit' => [SubmitJob::MAX_URLS_PER_REQUEST + 1];
    }

    #[DataProvider('rejectedBatchSizeProvider')]
    public function testRejectsBatchSizesOutsideTheProtocolLimit(int $batchSize): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Batch size must be between 1 and 10000');

        new SubmitJob(new InMemoryUrlStore(), new RecordingIndexNowClient(), $this->makeConfig(), $batchSize);
    }
}

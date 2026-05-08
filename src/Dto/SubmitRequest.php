<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Dto;

use SlimAD\IndexNow\Exception\InvalidConfigException;
use SlimAD\IndexNow\ValueObject\Host;
use SlimAD\IndexNow\ValueObject\Key;
use SlimAD\IndexNow\ValueObject\KeyLocation;
use SlimAD\IndexNow\ValueObject\Url;

final class SubmitRequest
{
    /** @var non-empty-list<Url> */
    public readonly array $urls;

    public function __construct(
        public readonly string $endpoint,
        public readonly Host $host,
        public readonly Key $key,
        public readonly KeyLocation $keyLocation,
        Url ...$urls,
    ) {
        if ($urls === []) {
            throw InvalidConfigException::emptyUrlList();
        }

        $deduped = [];
        foreach ($urls as $url) {
            if (!$host->matches($url)) {
                throw InvalidConfigException::urlDoesNotMatchHost($url->value, $host->value);
            }
            $deduped[$url->value] = $url;
        }

        $this->urls = array_values($deduped);
    }

    /**
     * @return array{host: string, key: string, keyLocation: string, urlList: non-empty-list<string>}
     */
    public function toArray(): array
    {
        return [
            'host' => $this->host->value,
            'key' => $this->key->value,
            'keyLocation' => $this->keyLocation->value(),
            'urlList' => array_map(static fn (Url $url): string => $url->value, $this->urls),
        ];
    }
}

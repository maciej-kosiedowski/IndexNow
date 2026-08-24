<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Config;

use SlimAD\IndexNow\ValueObject\Key;
use SlimAD\IndexNow\ValueObject\KeyLocation;
use SlimAD\IndexNow\ValueObject\Url;

final class SearchEngine
{
    public const ENDPOINT_INDEXNOW_API = 'https://api.indexnow.org/indexnow';
    public const ENDPOINT_BING = 'https://www.bing.com/indexnow';
    public const ENDPOINT_YANDEX = 'https://yandex.com/indexnow';
    public const ENDPOINT_SEZNAM = 'https://search.seznam.cz/indexnow';
    public const ENDPOINT_NAVER = 'https://searchadvisor.naver.com/indexnow';
    public const ENDPOINT_YEP = 'https://indexnow.yep.com/indexnow';

    public readonly string $endpoint;

    public function __construct(
        public readonly string $name,
        string $endpoint,
        public readonly Key $key,
        public readonly KeyLocation $keyLocation,
    ) {
        $this->endpoint = (new Url($endpoint))->value;
    }

    public static function bing(Key $key, KeyLocation $keyLocation): self
    {
        return new self('bing', self::ENDPOINT_BING, $key, $keyLocation);
    }

    public static function yandex(Key $key, KeyLocation $keyLocation): self
    {
        return new self('yandex', self::ENDPOINT_YANDEX, $key, $keyLocation);
    }

    public static function seznam(Key $key, KeyLocation $keyLocation): self
    {
        return new self('seznam', self::ENDPOINT_SEZNAM, $key, $keyLocation);
    }

    public static function naver(Key $key, KeyLocation $keyLocation): self
    {
        return new self('naver', self::ENDPOINT_NAVER, $key, $keyLocation);
    }

    public static function yep(Key $key, KeyLocation $keyLocation): self
    {
        return new self('yep', self::ENDPOINT_YEP, $key, $keyLocation);
    }

    public static function indexNowApi(Key $key, KeyLocation $keyLocation): self
    {
        return new self('indexnow', self::ENDPOINT_INDEXNOW_API, $key, $keyLocation);
    }
}

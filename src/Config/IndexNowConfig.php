<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Config;

use SlimAD\IndexNow\Exception\InvalidConfigException;
use SlimAD\IndexNow\ValueObject\Host;

final class IndexNowConfig
{
    /** @var non-empty-array<string, SearchEngine> */
    public readonly array $engines;

    /**
     * @param array<int, SearchEngine> $engines
     */
    public function __construct(
        public readonly Host $host,
        array $engines,
    ) {
        if ($engines === []) {
            throw InvalidConfigException::emptyEngines();
        }

        $indexed = [];
        foreach ($engines as $engine) {
            $indexed[$engine->name] = $engine;
        }

        /** @var non-empty-array<string, SearchEngine> $indexed */
        $this->engines = $indexed;
    }

    public function engine(string $name): ?SearchEngine
    {
        return $this->engines[$name] ?? null;
    }
}

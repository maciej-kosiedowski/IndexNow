<?php

namespace SlimAD\IndexNow\Contracts;

use SlimAD\IndexNow\DTO\IndexNowSubmitDTO;

interface IndexNowClient
{
    /**
     * Submit a URL to IndexNow.
     *
     * @param IndexNowSubmitDTO $dto
     * @param string $key
     * @return bool
     */
    public function submit(IndexNowSubmitDTO $dto, string $key): bool;
}

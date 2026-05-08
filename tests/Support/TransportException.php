<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class TransportException extends RuntimeException implements ClientExceptionInterface
{
}

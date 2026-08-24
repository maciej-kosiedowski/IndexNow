<?php

declare(strict_types=1);

namespace SlimAD\IndexNow\Tests\Unit\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SlimAD\IndexNow\Exception\InvalidKeyException;
use SlimAD\IndexNow\ValueObject\Key;

final class KeyTest extends TestCase
{
    public function testAcceptsValidKey(): void
    {
        $key = new Key('a1b2c3d4-e5f6');

        self::assertSame('a1b2c3d4-e5f6', $key->value);
        self::assertSame('a1b2c3d4-e5f6', (string) $key);
    }

    public function testAcceptsMinimumLength(): void
    {
        $key = new Key('abcdefgh');

        self::assertSame('abcdefgh', $key->value);
    }

    public function testAcceptsMaximumLength(): void
    {
        $value = str_repeat('a', 128);

        $key = new Key($value);

        self::assertSame($value, $key->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidLengthProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['abc1234'];
        yield 'too long' => [str_repeat('a', 129)];
    }

    #[DataProvider('invalidLengthProvider')]
    public function testRejectsInvalidLength(string $value): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('between 8 and 128');

        new Key($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCharactersProvider(): iterable
    {
        yield 'with space' => ['abcd efgh'];
        yield 'with slash' => ['abcd/efgh'];
        yield 'with underscore' => ['abcd_efgh'];
        yield 'with dot' => ['abcd.efgh'];
        yield 'with diacritic' => ['abcdefgh' . chr(0xc3) . chr(0xa9)];
    }

    #[DataProvider('invalidCharactersProvider')]
    public function testRejectsInvalidCharacters(string $value): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('letters, digits and dashes');

        new Key($value);
    }
}

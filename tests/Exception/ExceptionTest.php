<?php

declare(strict_types=1);

namespace LemoTest\Date\Exception;

use Lemo\Date\Exception\ExceptionInterface;
use Lemo\Date\Exception\InvalidArgumentException;
use Lemo\Date\Exception\ParseException;
use Lemo\Date\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(InvalidArgumentException::class)]
#[CoversClass(ParseException::class)]
#[CoversClass(RuntimeException::class)]
final class ExceptionTest extends TestCase
{
    /**
     * @param class-string<Throwable> $parent
     */
    #[DataProvider('exceptionProvider')]
    public function testHierarchy(Throwable $exception, string $parent): void
    {
        $this->assertInstanceOf(ExceptionInterface::class, $exception);
        $this->assertInstanceOf($parent, $exception);
        $this->assertSame('message', $exception->getMessage());
    }

    /**
     * @return iterable<string, array{Throwable, class-string<Throwable>}>
     */
    public static function exceptionProvider(): iterable
    {
        yield 'InvalidArgumentException' => [new InvalidArgumentException('message'), \InvalidArgumentException::class];
        yield 'RuntimeException' => [new RuntimeException('message'), \RuntimeException::class];
        yield 'ParseException is RuntimeException' => [new ParseException('message'), RuntimeException::class];
        yield 'ParseException is SPL RuntimeException' => [new ParseException('message'), \RuntimeException::class];
    }
}

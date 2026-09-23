<?php

declare(strict_types=1);

namespace LemoTest\Date;

use Lemo\Date\DiffInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiffInterval::class)]
final class DiffIntervalTest extends TestCase
{
    public function testDefaultsToZero(): void
    {
        $interval = new DiffInterval();

        $this->assertSame(0, $interval->getDays());
        $this->assertSame(0, $interval->getMonths());
        $this->assertSame(0, $interval->getYears());
    }

    #[DataProvider('valuesProvider')]
    public function testGettersReturnPublicProperties(int $days, int $months, int $years): void
    {
        $interval = new DiffInterval();
        $interval->days = $days;
        $interval->months = $months;
        $interval->years = $years;

        $this->assertSame($days, $interval->getDays());
        $this->assertSame($months, $interval->getMonths());
        $this->assertSame($years, $interval->getYears());
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function valuesProvider(): iterable
    {
        yield 'one day' => [1, 0, 0];
        yield 'one month' => [31, 1, 0];
        yield 'one year' => [365, 12, 1];
        yield 'distinct values' => [9032, 296, 24];
        yield 'large values' => [PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX];
    }
}

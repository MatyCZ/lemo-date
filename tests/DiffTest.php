<?php

declare(strict_types=1);

namespace LemoTest\Date;

use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Lemo\Date\Diff;
use Lemo\Date\Exception\ExceptionInterface;
use Lemo\Date\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(Diff::class)]
final class DiffTest extends TestCase
{
    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('differenceProvider')]
    public function testDifference(
        string $dateStart,
        string $dateEnd,
        bool $includeEndDay,
        bool $includeEveryStarted,
        int $days,
        int $months,
        int $years,
    ): void {
        $diff = new Diff($dateStart, $dateEnd, $includeEndDay, $includeEveryStarted);

        $this->assertSame(
            [$days, $months, $years],
            [$diff->getDays(), $diff->getMonths(), $diff->getYears()],
        );
    }

    /**
     * @return iterable<string, array{string, string, bool, bool, int, int, int}>
     */
    public static function differenceProvider(): iterable
    {
        $cases = [
            'consecutive days' => ['2024-01-01', '2024-01-02', [1, 0, 0], [2, 0, 0]],
            'one week' => ['2024-03-04', '2024-03-11', [7, 0, 0], [8, 0, 0]],
            'exact month' => ['2024-01-01', '2024-02-01', [31, 1, 0], [32, 1, 0]],
            'whole January' => ['2024-01-01', '2024-01-31', [30, 0, 0], [31, 1, 0]],
            'leap February' => ['2024-02-01', '2024-02-29', [28, 0, 0], [29, 1, 0]],
            'common February' => ['2023-02-01', '2023-02-28', [27, 0, 0], [28, 1, 0]],
            'Feb 28 to Mar 1, common year' => ['2023-02-28', '2023-03-01', [1, 0, 0], [2, 0, 0]],
            'Feb 28 to Mar 1, leap year' => ['2024-02-28', '2024-03-01', [2, 0, 0], [3, 0, 0]],
            'Jan 31 to Feb 29' => ['2024-01-31', '2024-02-29', [29, 0, 0], [30, 1, 0]],
            'whole common year' => ['2023-01-01', '2024-01-01', [365, 12, 1], [366, 12, 1]],
            'whole leap year' => ['2024-01-01', '2025-01-01', [366, 12, 1], [367, 12, 1]],
            'Jan 1 to Dec 31' => ['2024-01-01', '2024-12-31', [365, 11, 0], [366, 12, 1]],
            'Feb 29 to Feb 28' => ['2024-02-29', '2025-02-28', [365, 11, 0], [366, 12, 1]],
            'decade' => ['2014-06-15', '2024-06-15', [3653, 120, 10], [3654, 120, 10]],
            'since 2000' => ['2000-01-01', '2024-09-23', [9032, 296, 24], [9033, 296, 24]],
            'since 2000, d.m.Y format' => ['1.1.2000', '23.9.2024', [9032, 296, 24], [9033, 296, 24]],
        ];

        foreach ($cases as $name => [$start, $end, $exclusive, $inclusive]) {
            yield $name => [$start, $end, false, false, ...$exclusive];
            yield $name . ', end day included' => [$start, $end, true, false, ...$inclusive];
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('everyStartedProvider')]
    public function testEveryStartedCountsCalendarMonthsAndYears(
        string $dateStart,
        string $dateEnd,
        bool $includeEndDay,
        int $days,
        int $months,
        int $years,
    ): void {
        $diff = new Diff($dateStart, $dateEnd, $includeEndDay, includeEveryStarted: true);

        $this->assertSame(
            [$days, $months, $years],
            [$diff->getDays(), $diff->getMonths(), $diff->getYears()],
        );
    }

    /**
     * @return iterable<string, array{string, string, bool, int, int, int}>
     */
    public static function everyStartedProvider(): iterable
    {
        $cases = [
            'started month with earlier day of month' => ['2024-01-15', '2024-03-10', [55, 2, 0], [56, 2, 0]],
            'started year in the same month' => ['2024-03-20', '2025-03-10', [355, 12, 1], [356, 12, 1]],
            'month boundary after one day' => ['2024-01-31', '2024-02-01', [1, 1, 0], [2, 1, 0]],
            'year boundary after one day' => ['2024-12-31', '2025-01-01', [1, 1, 1], [2, 1, 1]],
            'within one month' => ['2024-06-01', '2024-06-30', [29, 0, 0], [30, 1, 0]],
            'exact month' => ['2024-01-01', '2024-02-01', [31, 1, 0], [32, 1, 0]],
            'exact year' => ['2023-01-01', '2024-01-01', [365, 12, 1], [366, 12, 1]],
            'Dec 31 to Dec 31' => ['1987-12-31', '2017-12-31', [10958, 360, 30], [10959, 361, 31]],
            'Jan 1 to mid-year' => ['1987-01-01', '2017-06-06', [11114, 365, 30], [11115, 365, 30]],
        ];

        foreach ($cases as $name => [$start, $end, $exclusive, $inclusive]) {
            yield $name => [$start, $end, false, ...$exclusive];
            yield $name . ', end day included' => [$start, $end, true, ...$inclusive];
        }
    }

    /**
     * Values relied upon by person age calculation (birth date to policy start or creation date,
     * end day included; "every started" is used for age by calendar year difference).
     *
     * @throws DateMalformedStringException
     */
    #[DataProvider('ageProvider')]
    public function testAgeCalculation(
        string $birthDate,
        string $referenceDate,
        bool $includeEveryStarted,
        int $days,
        int $months,
        int $years,
    ): void {
        $diff = new Diff(
            new DateTime($birthDate),
            new DateTime($referenceDate),
            includeEndDay: true,
            includeEveryStarted: $includeEveryStarted,
        );

        $this->assertSame(
            [$days, $months, $years],
            [$diff->getDays(), $diff->getMonths(), $diff->getYears()],
        );
    }

    /**
     * @return iterable<string, array{string, string, bool, int, int, int}>
     */
    public static function ageProvider(): iterable
    {
        $toPolicyStart = [
            '1.1.1987' => [11115, 365, 30, 365, 30],
            '3.2.1987' => [11082, 364, 30, 364, 30],
            '1.6.1987' => [10964, 360, 30, 360, 30],
            '31.12.1987' => [10751, 353, 29, 354, 30],
            '1.5.2017' => [37, 1, 0, 1, 0],
            '1.6.2017' => [6, 0, 0, 0, 0],
            '5.6.2017' => [2, 0, 0, 0, 0],
        ];

        foreach ($toPolicyStart as $birthDate => [$days, $months, $years, $calendarMonths, $calendarYears]) {
            yield 'born ' . $birthDate . ', completed' => [$birthDate, '6.6.2017', false, $days, $months, $years];
            yield 'born ' . $birthDate . ', calendar' => [$birthDate, '6.6.2017', true, $days, $calendarMonths, $calendarYears];
        }

        $toCreationDate = [
            '1.1.1987' => [11079, 364, 30],
            '3.2.1987' => [11046, 362, 30],
            '1.6.1987' => [10928, 359, 29],
            '31.12.1987' => [10715, 352, 29],
            '1.4.2017' => [31, 1, 0],
            '30.4.2017' => [2, 0, 0],
            '1.5.2017' => [1, 0, 0],
        ];

        foreach ($toCreationDate as $birthDate => [$days, $months, $years]) {
            yield 'born ' . $birthDate . ', to creation date' => [$birthDate, '2017-05-01', false, $days, $months, $years];
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testEveryStartedIsNeverBelowCompletedAndAtMostOneAbove(): void
    {
        $start = new DateTimeImmutable('2023-01-01');

        for ($startOffset = 0; $startOffset < 730; $startOffset += 13) {
            $dateStart = $start->modify('+' . $startOffset . ' days');

            foreach ([1, 15, 28, 29, 30, 31, 45, 59, 60, 364, 365, 366, 400, 730, 1461] as $length) {
                $dateEnd = $dateStart->modify('+' . $length . ' days');

                foreach ([false, true] as $includeEndDay) {
                    $completed = new Diff($dateStart, $dateEnd, $includeEndDay);
                    $started = new Diff($dateStart, $dateEnd, $includeEndDay, includeEveryStarted: true);
                    $label = $dateStart->format('Y-m-d') . ' - ' . $dateEnd->format('Y-m-d') . ($includeEndDay ? ' incl.' : '');

                    $this->assertSame($completed->getDays(), $started->getDays(), $label);
                    $this->assertContains($started->getMonths() - $completed->getMonths(), [0, 1], $label);
                    $this->assertContains($started->getYears() - $completed->getYears(), [0, 1], $label);
                }
            }
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('inputTypeProvider')]
    public function testInputTypesGiveSameResult(DateTimeInterface|string $dateStart, DateTimeInterface|string $dateEnd): void
    {
        $expected = [
            [false, false, [65, 2, 0]],
            [true, false, [66, 2, 0]],
            [false, true, [65, 2, 0]],
            [true, true, [66, 2, 0]],
        ];

        foreach ($expected as [$includeEndDay, $includeEveryStarted, $result]) {
            $diff = new Diff($dateStart, $dateEnd, $includeEndDay, $includeEveryStarted);

            $this->assertSame($result, [$diff->getDays(), $diff->getMonths(), $diff->getYears()]);
        }
    }

    /**
     * @return iterable<string, array{DateTimeInterface|string, DateTimeInterface|string}>
     *
     * @throws DateMalformedStringException
     */
    public static function inputTypeProvider(): iterable
    {
        $variants = static fn(string $date, string $czech): array => [
            'DateTime' => new DateTime($date),
            'DateTimeImmutable' => new DateTimeImmutable($date),
            'Y-m-d' => $date,
            'd.m.Y' => $czech,
            'Y-m-d H:i:s' => $date . ' 00:00:00',
        ];

        foreach ($variants('2024-01-15', '15.01.2024') as $startType => $dateStart) {
            foreach ($variants('2024-03-20', '20.3.2024') as $endType => $dateEnd) {
                yield $startType . ' to ' . $endType => [$dateStart, $dateEnd];
            }
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testNullEndMeansNow(): void
    {
        $dateStart = new DateTimeImmutable('-10 days');

        $this->assertSame(10, (new Diff($dateStart))->getDays());
        $this->assertSame(11, (new Diff($dateStart, null, includeEndDay: true))->getDays());
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testIntegerStartIsRejected(): void
    {
        $this->expectException(TypeError::class);

        /** @phpstan-ignore argument.type */
        (new Diff(1704067200, '2024-02-01'))->getDays();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testIntegerEndIsRejected(): void
    {
        $this->expectException(TypeError::class);

        /** @phpstan-ignore argument.type */
        (new Diff('2024-01-01', 1706745600))->getDays();
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('getterProvider')]
    public function testMalformedStartThrowsOnFirstGetter(string $getter): void
    {
        $diff = new Diff('nonsense', '2024-01-01');

        $this->expectException(DateMalformedStringException::class);

        $diff->{$getter}();
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('getterProvider')]
    public function testMalformedEndThrowsOnFirstGetter(string $getter): void
    {
        $diff = new Diff('2024-01-01', 'nonsense');

        $this->expectException(DateMalformedStringException::class);

        $diff->{$getter}();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function getterProvider(): iterable
    {
        yield 'getDays' => ['getDays'];
        yield 'getMonths' => ['getMonths'];
        yield 'getYears' => ['getYears'];
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('startAfterEndProvider')]
    public function testStartAfterEndThrows(DateTimeInterface|string $dateStart, DateTimeInterface|string $dateEnd, bool $includeEndDay): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Start date is greater than end date');

        (new Diff($dateStart, $dateEnd, $includeEndDay))->getDays();
    }

    /**
     * @return iterable<string, array{DateTimeInterface|string, DateTimeInterface|string, bool}>
     */
    public static function startAfterEndProvider(): iterable
    {
        yield 'one day' => ['2024-01-02', '2024-01-01', false];
        yield 'one day, end day included' => ['2024-01-02', '2024-01-01', true];
        yield 'one second' => ['2024-01-01 10:00:01', '2024-01-01 10:00:00', false];
        yield 'time of day against midnight' => ['2024-01-01 14:32:00', '2024-01-01', true];
        yield 'one year' => ['2025-01-01', '2024-01-01', false];
        yield 'DateTimeImmutable' => [new DateTimeImmutable('2024-06-01'), new DateTimeImmutable('2024-05-31'), false];
        yield 'mixed types' => [new DateTime('2024-06-01'), '31.5.2024', false];
        yield 'future start to now' => ['+1 day', 'now', false];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testStartAfterEndExceptionImplementsLibraryInterface(): void
    {
        try {
            (new Diff('2024-01-02', '2024-01-01'))->getDays();
            $this->fail('Exception was not thrown');
        } catch (ExceptionInterface $exception) {
            $this->assertInstanceOf(RuntimeException::class, $exception);
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('sameMomentProvider')]
    public function testSameMoment(
        DateTimeInterface|string $dateStart,
        DateTimeInterface|string $dateEnd,
        bool $includeEndDay,
        bool $includeEveryStarted,
    ): void {
        $diff = new Diff($dateStart, $dateEnd, $includeEndDay, $includeEveryStarted);

        // Zero days only when neither the end day nor every started day is counted
        $days = $includeEndDay || $includeEveryStarted ? 1 : 0;

        $this->assertSame([$days, 0, 0], [$diff->getDays(), $diff->getMonths(), $diff->getYears()]);
    }

    /**
     * @return iterable<string, array{DateTimeInterface|string, DateTimeInterface|string, bool, bool}>
     */
    public static function sameMomentProvider(): iterable
    {
        $sameObject = new DateTime('2024-05-05');

        $pairs = [
            'same string' => ['2024-05-05', '2024-05-05'],
            'different formats' => ['2024-05-05', '5.5.2024'],
            'same object' => [$sameObject, $sameObject],
            'DateTime and DateTimeImmutable' => [new DateTime('2024-05-05'), new DateTimeImmutable('2024-05-05')],
            'with time of day' => ['2024-05-05 13:45:10', '2024-05-05 13:45:10'],
            'leap day' => ['2024-02-29', '2024-02-29'],
        ];

        foreach ($pairs as $name => [$dateStart, $dateEnd]) {
            foreach ([false, true] as $includeEndDay) {
                foreach ([false, true] as $includeEveryStarted) {
                    $flags = sprintf(' (end day %s, every started %s)', $includeEndDay ? 'on' : 'off', $includeEveryStarted ? 'on' : 'off');

                    yield $name . $flags => [$dateStart, $dateEnd, $includeEndDay, $includeEveryStarted];
                }
            }
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('timeOfDayProvider')]
    public function testTimeOfDay(string $dateStart, string $dateEnd, bool $includeEndDay, int $days): void
    {
        $diff = new Diff($dateStart, $dateEnd, $includeEndDay);

        $this->assertSame([$days, 0, 0], [$diff->getDays(), $diff->getMonths(), $diff->getYears()]);
    }

    /**
     * @return iterable<string, array{string, string, bool, int}>
     */
    public static function timeOfDayProvider(): iterable
    {
        yield 'two hours over midnight' => ['2024-01-01 23:00', '2024-01-02 01:00', false, 0];
        yield 'two hours over midnight, end day included' => ['2024-01-01 23:00', '2024-01-02 01:00', true, 1];
        yield 'same day, different time' => ['2024-01-01 00:00', '2024-01-01 12:00', false, 0];
        yield 'same day, different time, end day included' => ['2024-01-01 00:00', '2024-01-01 12:00', true, 1];
        yield 'one second short of a day' => ['2024-01-01 12:00:00', '2024-01-02 11:59:59', false, 0];
        yield 'exactly one day' => ['2024-01-01 12:00:00', '2024-01-02 12:00:00', false, 1];
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('daylightSavingProvider')]
    public function testDaylightSavingTransitionDoesNotChangeDayCount(string $dateStart, string $dateEnd, int $days): void
    {
        $this->assertSame($days, (new Diff($dateStart, $dateEnd))->getDays());
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function daylightSavingProvider(): iterable
    {
        yield 'spring, midnight' => ['2024-03-30', '2024-04-01', 2];
        yield 'spring, noon (23 hours)' => ['2024-03-30 12:00', '2024-03-31 12:00', 1];
        yield 'autumn, midnight' => ['2024-10-26', '2024-10-28', 2];
        yield 'autumn, noon (25 hours)' => ['2024-10-26 12:00', '2024-10-27 12:00', 1];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testCallerObjectsAreNotModified(): void
    {
        $dateStart = new DateTime('2024-01-15 08:30:00');
        $dateEnd = new DateTime('2024-03-20 16:45:00');

        $diff = new Diff($dateStart, $dateEnd, includeEndDay: true, includeEveryStarted: true);
        $diff->getDays();

        $this->assertSame('2024-01-15 08:30:00', $dateStart->format('Y-m-d H:i:s'));
        $this->assertSame('2024-03-20 16:45:00', $dateEnd->format('Y-m-d H:i:s'));
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testResultIsStableAfterFirstCalculation(): void
    {
        $dateEnd = new DateTime('2024-03-20');
        $diff = new Diff('2024-01-15', $dateEnd);

        $this->assertSame(65, $diff->getDays());

        $dateEnd->modify('+1 year');

        $this->assertSame(65, $diff->getDays());
        $this->assertSame(2, $diff->getMonths());
        $this->assertSame(0, $diff->getYears());
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testGetterOrderDoesNotMatter(): void
    {
        $first = new Diff('2000-01-01', '2024-09-23');
        $second = new Diff('2000-01-01', '2024-09-23');

        $firstResult = [$first->getDays(), $first->getMonths(), $first->getYears()];
        $secondResult = array_reverse([$second->getYears(), $second->getMonths(), $second->getDays()]);

        $this->assertSame($firstResult, $secondResult);
    }
}

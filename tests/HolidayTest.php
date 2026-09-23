<?php

declare(strict_types=1);

namespace LemoTest\Date;

use ArrayIterator;
use ArrayObject;
use DateMalformedStringException;
use Generator;
use Lemo\Date\Exception\InvalidArgumentException;
use Lemo\Date\Exception\ParseException;
use Lemo\Date\Holiday;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

use function array_key_last;
use function array_keys;
use function date;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function sort;
use function substr;

/**
 * @phpstan-import-type HolidayDefinition from Holiday
 */
#[CoversClass(Holiday::class)]
final class HolidayTest extends TestCase
{
    private const array LIST_2024 = [
        '2024-01-01' => 'Den obnovy samostatného českého státu',
        '2024-03-29' => 'Velký pátek',
        '2024-04-01' => 'Velikonoční pondělí',
        '2024-05-01' => 'Svátek práce',
        '2024-05-08' => 'Den vítězství',
        '2024-07-05' => 'Den slovanských věrozvěstů Cyrila a Metoděje',
        '2024-07-06' => 'Den upálení mistra Jana Husa',
        '2024-09-28' => 'Den české státnosti',
        '2024-10-28' => 'Den vzniku samostatného československého státu',
        '2024-11-17' => 'Den boje za svobodu a demokracii a Mezinárodní den studentstva',
        '2024-12-24' => 'Štědrý den',
        '2024-12-25' => '1. svátek vánoční',
        '2024-12-26' => '2. svátek vánoční',
    ];

    private string $timezone;

    protected function setUp(): void
    {
        $this->timezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);
    }

    /**
     * @param iterable<string, mixed> $options
     */
    #[DataProvider('constructorOptionsProvider')]
    public function testCountryFromConstructorOptions(iterable $options): void
    {
        $this->assertSame('CZ', (new Holiday($options))->getCountry());
    }

    /**
     * @return iterable<string, array{iterable<string, mixed>}>
     */
    public static function constructorOptionsProvider(): iterable
    {
        yield 'array, lower case' => [['country' => 'cz']];
        yield 'array, upper case' => [['country' => 'CZ']];
        yield 'array, mixed case' => [['country' => 'Cz']];
        yield 'array with other keys' => [['locale' => 'cs_CZ', 'country' => 'cz', 'other' => null]];
        yield 'ArrayObject' => [new ArrayObject(['country' => 'cz'])];
        yield 'ArrayIterator' => [new ArrayIterator(['country' => 'cz'])];
        yield 'generator' => [self::generateOptions()];
    }

    /**
     * @return Generator<string, string>
     */
    private static function generateOptions(): Generator
    {
        yield 'country' => 'cz';
    }

    /**
     * @param iterable<string, mixed>|null $options
     */
    #[DataProvider('missingCountryProvider')]
    public function testMissingCountryThrows(?iterable $options): void
    {
        $holiday = new Holiday($options);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(Holiday::class . ' expects a "country" option; none given');

        $holiday->getCountry();
    }

    /**
     * @return iterable<string, array{iterable<string, mixed>|null}>
     */
    public static function missingCountryProvider(): iterable
    {
        yield 'no options' => [null];
        yield 'empty array' => [[]];
        yield 'empty ArrayObject' => [new ArrayObject()];
        yield 'other keys only' => [['locale' => 'cs_CZ']];
        yield 'empty string' => [['country' => '']];
        yield 'string zero' => [['country' => '0']];
    }

    #[DataProvider('nonStringCountryProvider')]
    public function testNonStringCountryIsRejected(mixed $country): void
    {
        $this->expectException(TypeError::class);

        new Holiday(['country' => $country]);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonStringCountryProvider(): iterable
    {
        yield 'null' => [null];
        yield 'integer' => [420];
        yield 'array' => [['CZ']];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testMissingCountryThrowsFromList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Holiday())->getListForYear(2024);
    }

    public function testSetCountryIsFluentAndUpperCases(): void
    {
        $holiday = new Holiday();

        $this->assertSame($holiday, $holiday->setCountry('cz'));
        $this->assertSame('CZ', $holiday->getCountry());
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testSetCountryOverridesConstructorOption(): void
    {
        $holiday = new Holiday(['country' => 'sk']);
        $holiday->setCountry('cz');

        $this->assertSame('CZ', $holiday->getCountry());
        $this->assertSame(self::LIST_2024, $holiday->getListForYear(2024));
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('unknownCountryProvider')]
    public function testUnknownCountryThrows(string $country, string $normalized): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Pattern file for country '" . $normalized . "' was not found");

        (new Holiday(['country' => $country]))->getListForYear(2024);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unknownCountryProvider(): iterable
    {
        yield 'unsupported country' => ['de', 'DE'];
        yield 'non-existent code' => ['XX', 'XX'];
        yield 'three letters' => ['CZE', 'CZE'];
        yield 'one letter' => ['C', 'C'];
        yield 'digit' => ['C1', 'C1'];
        yield 'path traversal' => ['../CZ', '../CZ'];
        yield 'leading space' => [' CZ', ' CZ'];
        yield 'trailing space' => ['CZ ', 'CZ '];
        yield 'trailing new line' => ["CZ\n", "CZ\n"];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testListFor2024(): void
    {
        $this->assertSame(self::LIST_2024, (new Holiday(['country' => 'cz']))->getListForYear(2024));
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('holidayCountProvider')]
    public function testListIsCompleteAndSorted(string $country, int $year, int $count): void
    {
        $list = (new Holiday(['country' => $country]))->getListForYear($year);
        $dates = array_keys($list);
        $sorted = $dates;
        sort($sorted);

        $this->assertCount($count, $list);
        $this->assertSame($sorted, $dates);

        foreach ($dates as $date) {
            $this->assertMatchesRegularExpression('/^' . $year . '-\d{2}-\d{2}$/', $date);
        }
    }

    /**
     * @return iterable<string, array{string, int, int}>
     */
    public static function holidayCountProvider(): iterable
    {
        $counts = [
            'CZ' => [1990 => 10, 1993 => 10, 1999 => 10, 2000 => 12, 2015 => 12, 2016 => 13],
            'SK' => [
                1990 => 10, 1993 => 10, 1994 => 13, 1996 => 13, 1997 => 14, 2000 => 14, 2001 => 15, 2017 => 15,
                2018 => 16, 2019 => 15, 2023 => 15, 2024 => 14, 2025 => 13, 2026 => 11, 2027 => 13,
            ],
        ];

        foreach ($counts as $country => $years) {
            foreach ($years as $year => $count) {
                yield $country . ' ' . $year => [$country, $year, $count];
            }

            // Without further changes the latest state applies to future years as well
            $lastYear = array_key_last($years);

            for ($year = $lastYear + 1; $year <= 2040; ++$year) {
                yield $country . ' ' . $year => [$country, $year, $years[$lastYear]];
            }
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('historyProvider')]
    public function testHistory(string $country, string $date, ?string $name): void
    {
        $list = (new Holiday(['country' => $country]))->getListForYear((int) substr($date, 0, 4));

        $this->assertSame($name, $list[$date] ?? null);
    }

    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function historyProvider(): iterable
    {
        $history = [
            'CZ' => [
                '1990-10-28' => 'Den vzniku samostatného československého státu',
                '1995-04-17' => 'Velikonoční pondělí',
                '2000-01-01' => 'Nový rok',
                '2001-01-01' => 'Den obnovy samostatného českého státu',
                '2000-05-08' => 'Den osvobození od fašismu',
                '2001-05-08' => 'Den osvobození',
                '2003-05-08' => 'Den osvobození',
                '2004-05-08' => 'Den vítězství',
                '1999-09-28' => null,
                '2000-09-28' => 'Den české státnosti',
                '1999-11-17' => null,
                '2000-11-17' => 'Den boje za svobodu a demokracii',
                '2018-11-17' => 'Den boje za svobodu a demokracii',
                '2019-11-17' => 'Den boje za svobodu a demokracii a Mezinárodní den studentstva',
                '2015-04-03' => null,
                '2016-03-25' => 'Velký pátek',
            ],
            'SK' => [
                '1990-10-28' => 'Deň vzniku samostatného česko-slovenského štátu',
                '1993-01-06' => null,
                '1994-01-06' => 'Zjavenie Pána (Traja králi a vianočný sviatok pravoslávnych kresťanov)',
                '1993-04-09' => null,
                '1994-04-01' => 'Veľký piatok',
                '1993-04-12' => 'Veľkonočný pondelok',
                '1993-05-08' => 'Deň víťazstva nad fašizmom',
                '1994-05-08' => null,
                '1996-05-08' => null,
                '1997-05-08' => 'Deň víťazstva nad fašizmom',
                '2025-05-08' => 'Deň víťazstva nad fašizmom',
                '2026-05-08' => null,
                '2027-05-08' => 'Deň víťazstva nad fašizmom',
                '1993-09-01' => null,
                '1994-09-01' => 'Deň Ústavy Slovenskej republiky',
                '2023-09-01' => 'Deň Ústavy Slovenskej republiky',
                '2024-09-01' => null,
                '1993-09-15' => null,
                '2025-09-15' => 'Sedembolestná Panna Mária',
                '2026-09-15' => null,
                '2027-09-15' => 'Sedembolestná Panna Mária',
                '1993-10-28' => 'Deň vzniku samostatného česko-slovenského štátu',
                '1994-10-28' => null,
                '2017-10-30' => null,
                '2018-10-30' => 'Výročie Deklarácie slovenského národa',
                '2019-10-30' => null,
                '1993-11-01' => null,
                '1994-11-01' => 'Sviatok Všetkých svätých',
                '2000-11-17' => null,
                '2001-11-17' => 'Deň boja za slobodu a demokraciu',
                '2024-11-17' => 'Deň boja za slobodu a demokraciu',
                '2025-11-17' => null,
            ],
        ];

        foreach ($history as $country => $dates) {
            foreach ($dates as $date => $name) {
                yield $country . ' ' . $date => [$country, $date, $name];
            }
        }
    }

    /**
     * @param array<string, string> $expected
     *
     * @throws DateMalformedStringException
     */
    #[DataProvider('slovakListProvider')]
    public function testSlovakList(int $year, array $expected): void
    {
        $this->assertSame($expected, (new Holiday(['country' => 'sk']))->getListForYear($year));
    }

    /**
     * @return iterable<string, array{int, array<string, string>}>
     */
    public static function slovakListProvider(): iterable
    {
        yield '2024' => [2024, [
            '2024-01-01' => 'Deň vzniku Slovenskej republiky',
            '2024-01-06' => 'Zjavenie Pána (Traja králi a vianočný sviatok pravoslávnych kresťanov)',
            '2024-03-29' => 'Veľký piatok',
            '2024-04-01' => 'Veľkonočný pondelok',
            '2024-05-01' => 'Sviatok práce',
            '2024-05-08' => 'Deň víťazstva nad fašizmom',
            '2024-07-05' => 'Sviatok svätého Cyrila a svätého Metoda',
            '2024-08-29' => 'Výročie Slovenského národného povstania',
            '2024-09-15' => 'Sedembolestná Panna Mária',
            '2024-11-01' => 'Sviatok Všetkých svätých',
            '2024-11-17' => 'Deň boja za slobodu a demokraciu',
            '2024-12-24' => 'Štedrý deň',
            '2024-12-25' => 'Prvý sviatok vianočný',
            '2024-12-26' => 'Druhý sviatok vianočný',
        ]];

        yield '2026' => [2026, [
            '2026-01-01' => 'Deň vzniku Slovenskej republiky',
            '2026-01-06' => 'Zjavenie Pána (Traja králi a vianočný sviatok pravoslávnych kresťanov)',
            '2026-04-03' => 'Veľký piatok',
            '2026-04-06' => 'Veľkonočný pondelok',
            '2026-05-01' => 'Sviatok práce',
            '2026-07-05' => 'Sviatok svätého Cyrila a svätého Metoda',
            '2026-08-29' => 'Výročie Slovenského národného povstania',
            '2026-11-01' => 'Sviatok Všetkých svätých',
            '2026-12-24' => 'Štedrý deň',
            '2026-12-25' => 'Prvý sviatok vianočný',
            '2026-12-26' => 'Druhý sviatok vianočný',
        ]];

        yield '2027' => [2027, [
            '2027-01-01' => 'Deň vzniku Slovenskej republiky',
            '2027-01-06' => 'Zjavenie Pána (Traja králi a vianočný sviatok pravoslávnych kresťanov)',
            '2027-03-26' => 'Veľký piatok',
            '2027-03-29' => 'Veľkonočný pondelok',
            '2027-05-01' => 'Sviatok práce',
            '2027-05-08' => 'Deň víťazstva nad fašizmom',
            '2027-07-05' => 'Sviatok svätého Cyrila a svätého Metoda',
            '2027-08-29' => 'Výročie Slovenského národného povstania',
            '2027-09-15' => 'Sedembolestná Panna Mária',
            '2027-11-01' => 'Sviatok Všetkých svätých',
            '2027-12-24' => 'Štedrý deň',
            '2027-12-25' => 'Prvý sviatok vianočný',
            '2027-12-26' => 'Druhý sviatok vianočný',
        ]];
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('easterProvider')]
    public function testEasterHolidaysInList(int $year, string $goodFriday, string $easterMonday): void
    {
        $list = (new Holiday(['country' => 'CZ']))->getListForYear($year);

        $this->assertSame('Velký pátek', $list[$goodFriday] ?? null);
        $this->assertSame('Velikonoční pondělí', $list[$easterMonday] ?? null);
    }

    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function easterProvider(): iterable
    {
        yield '2016' => [2016, '2016-03-25', '2016-03-28'];
        yield '2019' => [2019, '2019-04-19', '2019-04-22'];
        yield '2024, across month boundary' => [2024, '2024-03-29', '2024-04-01'];
        yield '2025' => [2025, '2025-04-18', '2025-04-21'];
        yield '2026' => [2026, '2026-04-03', '2026-04-06'];
        yield '2038' => [2038, '2038-04-23', '2038-04-26'];
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('easterDatesProvider')]
    public function testEasterDates(int $year, string $goodFriday, string $easterSunday, string $easterMonday): void
    {
        $holiday = new class extends Holiday {
            /**
             * @return array<string, string>
             */
            public function dynamicDates(int $year): array
            {
                return $this->createDynamicDates($year);
            }
        };

        $this->assertSame(
            [
                Holiday::EASTER_FRIDAY => $goodFriday,
                Holiday::EASTER_SUNDAY => $easterSunday,
                Holiday::EASTER_MONDAY => $easterMonday,
            ],
            $holiday->dynamicDates($year),
        );
    }

    /**
     * @return iterable<string, array{int, string, string, string}>
     */
    public static function easterDatesProvider(): iterable
    {
        yield '1818, earliest possible' => [1818, '1818-03-20', '1818-03-22', '1818-03-23'];
        yield '1900' => [1900, '1900-04-13', '1900-04-15', '1900-04-16'];
        yield '1943, latest possible' => [1943, '1943-04-23', '1943-04-25', '1943-04-26'];
        yield '1969, before Unix epoch' => [1969, '1969-04-04', '1969-04-06', '1969-04-07'];
        yield '2000' => [2000, '2000-04-21', '2000-04-23', '2000-04-24'];
        yield '2008, Good Friday in March' => [2008, '2008-03-21', '2008-03-23', '2008-03-24'];
        yield '2011' => [2011, '2011-04-22', '2011-04-24', '2011-04-25'];
        yield '2024, Monday in April' => [2024, '2024-03-29', '2024-03-31', '2024-04-01'];
        yield '2025' => [2025, '2025-04-18', '2025-04-20', '2025-04-21'];
        yield '2026' => [2026, '2026-04-03', '2026-04-05', '2026-04-06'];
        yield '2038, after 32-bit limit' => [2038, '2038-04-23', '2038-04-25', '2038-04-26'];
        yield '2100' => [2100, '2100-03-26', '2100-03-28', '2100-03-29'];
        yield '2285, earliest possible' => [2285, '2285-03-20', '2285-03-22', '2285-03-23'];
    }

    /**
     * @throws DateMalformedStringException
     */
    #[DataProvider('timezoneProvider')]
    public function testListDoesNotDependOnTimezone(string $timezone): void
    {
        date_default_timezone_set($timezone);

        $this->assertSame(self::LIST_2024, (new Holiday(['country' => 'CZ']))->getListForYear(2024));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function timezoneProvider(): iterable
    {
        foreach (['UTC', 'Europe/Prague', 'America/Los_Angeles', 'Pacific/Pago_Pago', 'Pacific/Auckland', 'Pacific/Kiritimati', 'Asia/Kathmandu'] as $timezone) {
            yield $timezone => [$timezone];
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testYearZeroMeansCurrentYear(): void
    {
        $holiday = new Holiday(['country' => 'CZ']);

        $this->assertSame($holiday->getListForYear((int) date('Y')), $holiday->getListForYear(0));
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testGetListReturnsCurrentYear(): void
    {
        $holiday = new Holiday(['country' => 'CZ']);

        $this->assertSame($holiday->getListForYear((int) date('Y')), $holiday->getList());
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testInstancesShareLoadedPattern(): void
    {
        $upperCase = (new Holiday(['country' => 'CZ']))->getListForYear(2024);
        $lowerCase = (new Holiday(['country' => 'cz']))->getListForYear(2024);

        $this->assertSame($upperCase, $lowerCase);
    }

    /**
     * @param array<string, HolidayDefinition> $static
     * @param array<string, HolidayDefinition> $dynamic
     *
     * @throws DateMalformedStringException
     */
    #[DataProvider('badPatternProvider')]
    public function testBadPatternThrows(array $static, array $dynamic): void
    {
        $holiday = $this->createHolidayWithPattern($static, $dynamic);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Pattern file for country 'XX' has bad format");

        $holiday->getListForYear(2024);
    }

    /**
     * @return iterable<string, array{array<string, HolidayDefinition>, array<string, HolidayDefinition>}>
     */
    public static function badPatternProvider(): iterable
    {
        yield 'no static holidays' => [[], [Holiday::EASTER_MONDAY => [['name' => 'Easter Monday']]]];
        yield 'no dynamic holidays' => [['01-01' => [['name' => 'New Year']]], []];
        yield 'empty pattern' => [[], []];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testDynamicHolidayWinsOverStaticOnSameDate(): void
    {
        $holiday = $this->createHolidayWithPattern(
            [
                '03-29' => [['name' => 'Static']],
                '12-25' => [['name' => 'Christmas']],
            ],
            [
                Holiday::EASTER_FRIDAY => [['name' => 'Good Friday']],
                Holiday::EASTER_SUNDAY => [['name' => 'Easter Sunday']],
            ],
        );

        $this->assertSame(
            [
                '2024-03-29' => 'Good Friday',
                '2024-03-31' => 'Easter Sunday',
                '2024-12-25' => 'Christmas',
            ],
            $holiday->getListForYear(2024),
        );
    }

    /**
     * @param array<string, string> $expected
     *
     * @throws DateMalformedStringException
     */
    #[DataProvider('yearRestrictionProvider')]
    public function testYearRestrictions(int $year, array $expected): void
    {
        $holiday = $this->createHolidayWithPattern(
            [
                '01-01' => [['name' => 'Always']],
                '02-01' => [['name' => 'From 2020', 'from' => 2020]],
                '03-01' => [['name' => 'Until 2020', 'to' => 2020]],
                '04-01' => [['name' => 'Range', 'from' => 2019, 'to' => 2021]],
                '05-01' => [['name' => 'Except 2020', 'except' => [2020]]],
                '06-01' => [['name' => 'Old', 'to' => 2019], ['name' => 'New', 'from' => 2020]],
                '07-01' => [['name' => 'Gap before', 'to' => 2017], ['name' => 'Gap after', 'from' => 2022]],
                '08-01' => [['name' => 'First', 'from' => 2020], ['name' => 'Fallback']],
            ],
            [Holiday::EASTER_MONDAY => [['name' => 'Easter Monday', 'from' => 2020, 'except' => [2021]]]],
        );

        $this->assertSame($expected, $holiday->getListForYear($year));
    }

    /**
     * @return iterable<string, array{int, array<string, string>}>
     */
    public static function yearRestrictionProvider(): iterable
    {
        yield '2017' => [2017, [
            '2017-01-01' => 'Always',
            '2017-03-01' => 'Until 2020',
            '2017-05-01' => 'Except 2020',
            '2017-06-01' => 'Old',
            '2017-07-01' => 'Gap before',
            '2017-08-01' => 'Fallback',
        ]];

        yield '2019' => [2019, [
            '2019-01-01' => 'Always',
            '2019-03-01' => 'Until 2020',
            '2019-04-01' => 'Range',
            '2019-05-01' => 'Except 2020',
            '2019-06-01' => 'Old',
            '2019-08-01' => 'Fallback',
        ]];

        yield '2020' => [2020, [
            '2020-01-01' => 'Always',
            '2020-02-01' => 'From 2020',
            '2020-03-01' => 'Until 2020',
            '2020-04-01' => 'Range',
            '2020-04-13' => 'Easter Monday',
            '2020-06-01' => 'New',
            '2020-08-01' => 'First',
        ]];

        yield '2021' => [2021, [
            '2021-01-01' => 'Always',
            '2021-02-01' => 'From 2020',
            '2021-04-01' => 'Range',
            '2021-05-01' => 'Except 2020',
            '2021-06-01' => 'New',
            '2021-08-01' => 'First',
        ]];

        yield '2022' => [2022, [
            '2022-01-01' => 'Always',
            '2022-02-01' => 'From 2020',
            '2022-04-18' => 'Easter Monday',
            '2022-05-01' => 'Except 2020',
            '2022-06-01' => 'New',
            '2022-07-01' => 'Gap after',
            '2022-08-01' => 'First',
        ]];
    }

    /**
     * @param array<string, HolidayDefinition> $static
     * @param array<string, HolidayDefinition> $dynamic
     */
    private function createHolidayWithPattern(array $static, array $dynamic): Holiday
    {
        return new class ($static, $dynamic) extends Holiday {
            /**
             * @param array<string, list<array{name: string, from?: int, to?: int, except?: list<int>}>> $static
             * @param array<string, list<array{name: string, from?: int, to?: int, except?: list<int>}>> $dynamic
             */
            public function __construct(
                private readonly array $static,
                private readonly array $dynamic,
            ) {
                parent::__construct(['country' => 'XX']);
            }

            protected function loadPattern(string $code): array
            {
                return ['static' => $this->static, 'dynamic' => $this->dynamic];
            }
        };
    }
}

# lemo-date

A small set of date utilities for PHP:

- **`Diff`** – difference between two dates in days, months and years, optionally including the end day or counting every started month/year.
- **`Holiday`** – public holidays (non-working days) for a given year, including their history since 1993. Supported countries: Czechia (`CZ`) and Slovakia (`SK`).

## Requirements

- PHP 8.3 or newer
- `ext-calendar`

## Installation

```bash
composer require matycz/lemo-date
```

## Diff

```php
use Lemo\Date\Diff;

$diff = new Diff('2024-01-15', new DateTimeImmutable('2024-03-10'));

$diff->getDays();   // 55
$diff->getMonths(); // 1
$diff->getYears();  // 0
```

```php
new Diff(
    DateTimeInterface|string $dateStart,
    DateTimeInterface|string|null $dateEnd = null,
    bool $includeEndDay = false,
    bool $includeEveryStarted = false,
);
```

| Argument | Description |
|---|---|
| `$dateStart`, `$dateEnd` | A `DateTime`, a `DateTimeImmutable` or any string accepted by the `DateTime` constructor (`'2024-01-15'`, `'15.1.2024'`, …). `null` as the end date means *now*. Integers are not accepted; convert a Unix timestamp explicitly, e.g. `(new DateTimeImmutable())->setTimestamp($timestamp)`. |
| `$includeEndDay` | The end day counts as a whole day, so the end date is moved by one day before the calculation. |
| `$includeEveryStarted` | Months and years are counted as a calendar difference, so every started month/year counts. The result is never lower than the number of completed months/years and at most one higher. |

Behaviour:

- `getDays()` returns the number of whole days, `getMonths()` and `getYears()` the number of completed months and years.
- The difference is calculated on the first getter call and then cached. The objects passed in are never modified.
- Both dates are compared including the time of day. If the start is later than the end, `Lemo\Date\Exception\RuntimeException` is thrown, e.g. for `2024-01-01 14:00` → `2024-01-01`.
- An invalid date string throws `DateMalformedStringException` on the first getter call.

Examples:

| Start | End | Options | Days | Months | Years |
|---|---|---|---|---|---|
| 2024-01-15 | 2024-03-10 | – | 55 | 1 | 0 |
| 2024-01-15 | 2024-03-10 | `includeEndDay` | 56 | 1 | 0 |
| 2024-01-15 | 2024-03-10 | `includeEveryStarted` | 55 | 2 | 0 |
| 2024-01-01 | 2024-01-31 | `includeEndDay` | 31 | 1 | 0 |
| 2024-03-20 | 2025-03-10 | `includeEveryStarted` | 355 | 12 | 1 |
| 2024-05-05 | 2024-05-05 | – | 0 | 0 | 0 |
| 2024-05-05 | 2024-05-05 | `includeEndDay` or `includeEveryStarted` | 1 | 0 | 0 |

## Holiday

```php
use Lemo\Date\Holiday;

$holiday = new Holiday(['country' => 'CZ']);

$holiday->getList();            // holidays of the current year
$holiday->getListForYear(2024); // holidays of 2024
```

The result is an array sorted by date, with dates in `Y-m-d` format as keys and holiday names as values:

```php
[
    '2024-01-01' => 'Den obnovy samostatného českého státu',
    '2024-03-29' => 'Velký pátek',
    '2024-04-01' => 'Velikonoční pondělí',
    // ...
]
```

- The country is an ISO 3166-1 alpha-2 code, case-insensitive. Set it through the `country` option (an array or any `Traversable`) or with `setCountry()`.
- The list contains only days that are non-working days in the given year. For example, the Slovak list for 2026 does not contain 8 May and 15 September.
- Years before 1993 use the state of 1993. Future years use the law currently in force.
- Easter is calculated with `easter_days()`, so the result does not depend on the time zone.
- `getListForYear(0)` returns the current year.

Exceptions:

- `Lemo\Date\Exception\InvalidArgumentException` – no country is set, or there is no pattern for the country.
- `Lemo\Date\Exception\ParseException` – the pattern contains no fixed or no Easter-based holidays.

All library exceptions implement `Lemo\Date\Exception\ExceptionInterface`.

### Holiday patterns

Each country is described by a pattern file in `src/Holiday/Pattern/<COUNTRY>.php`. `static` holidays are keyed by `MM-DD`. `dynamic` holidays are keyed by the Easter constants `Holiday::EASTER_FRIDAY`, `Holiday::EASTER_SUNDAY` and `Holiday::EASTER_MONDAY`.

Every holiday is a list of variants. A variant may be restricted to years:

- `from`, `to` – the first and last year (inclusive);
- `except` – a list of excluded years.

The first variant valid for the year is used, which is how name changes are recorded. A holiday without a valid variant is not in the list for that year.

```php
return [
    'static' => [
        '05-01' => [
            ['name' => 'Svátek práce'],
        ],
        '05-08' => [
            ['name' => 'Den osvobození od fašismu', 'to' => 2000],
            ['name' => 'Den osvobození', 'from' => 2001, 'to' => 2003],
            ['name' => 'Den vítězství', 'from' => 2004],
        ],
        '09-15' => [
            ['name' => 'Sedembolestná Panna Mária', 'from' => 1994, 'except' => [2026]],
        ],
    ],
    'dynamic' => [
        Holiday::EASTER_FRIDAY => [
            ['name' => 'Velký pátek', 'from' => 2016],
        ],
    ],
];
```

To use your own pattern without adding a file, extend `Holiday` and override `loadPattern()`.

## License

BSD-3-Clause, see [LICENSE](LICENSE).

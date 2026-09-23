<?php

declare(strict_types=1);

namespace Lemo\Date;

use DateMalformedStringException;
use DateTimeImmutable;
use Traversable;

use function array_key_exists;
use function date;
use function easter_days;
use function file_exists;
use function in_array;
use function iterator_to_array;
use function ksort;
use function preg_match;
use function sprintf;
use function strtoupper;

/**
 * A holiday is a list of variants optionally restricted to years (from/to inclusive, except
 * listed years); the first variant valid for the year wins, which covers name changes.
 *
 * @phpstan-type HolidayVariant array{name: string, from?: int, to?: int, except?: list<int>}
 * @phpstan-type HolidayDefinition list<HolidayVariant>
 * @phpstan-type HolidayPattern array{static: array<string, HolidayDefinition>, dynamic: array<string, HolidayDefinition>}
 */
class Holiday
{
    public const string EASTER_FRIDAY = 'easterFriday';

    public const string EASTER_MONDAY = 'easterMonday';

    public const string EASTER_SUNDAY = 'easterSunday';

    /**
     * ISO 3611 Country Code
     */
    protected ?string $country = null;

    /**
     * Day patterns
     *
     * @var array<string, HolidayPattern>
     */
    protected static array $days = [];

    /**
     * Constructor
     *
     * Options
     * - country | string | field or value
     *
     * @param iterable<string, mixed>|null $options
     */
    public function __construct(?iterable $options = null)
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        if (null !== $options) {
            if (array_key_exists('country', $options)) {
                $this->setCountry($options['country']);
            }
        }
    }

    /**
     * @return array<string, string>
     *
     * @throws DateMalformedStringException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\ParseException
     */
    protected function createList(int $year): array
    {
        $country = $this->getCountry();

        // Load pattern
        $daysPattern = $this->loadPattern($country);

        // Check if patern is loaded
        if (null === $daysPattern) {
            throw new Exception\InvalidArgumentException(
                "Pattern file for country '$country' was not found",
            );
        }

        // Pattern
        if (empty($daysPattern['dynamic']) || empty($daysPattern['static'])) {
            throw new Exception\ParseException(
                "Pattern file for country '$country' has bad format",
            );
        }

        $year = $year ?: (int) date('Y');

        $dynamicDates = $this->createDynamicDates($year);
        $holidays = [];

        // Static holidays
        foreach ($daysPattern['static'] as $date => $definition) {
            $name = $this->resolveName($definition, $year);

            if (null !== $name) {
                $holidays[$year . '-' . $date] = $name;
            }
        }

        // Dynamic holidays
        foreach ($daysPattern['dynamic'] as $pattern => $definition) {
            $name = $this->resolveName($definition, $year);

            if (null !== $name) {
                $holidays[$dynamicDates[$pattern]] = $name;
            }
        }

        ksort($holidays);

        return $holidays;
    }

    /**
     * Name of the holiday valid in the given year, null when it is not a holiday that year
     *
     * @param HolidayDefinition $definition
     */
    protected function resolveName(array $definition, int $year): ?string
    {
        foreach ($definition as $variant) {
            if (
                $year >= ($variant['from'] ?? PHP_INT_MIN)
                && $year <= ($variant['to'] ?? PHP_INT_MAX)
                && !in_array($year, $variant['except'] ?? [], true)
            ) {
                return $variant['name'];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     *
     * @throws DateMalformedStringException
     */
    protected function createDynamicDates(int $year): array
    {
        // Easter Sunday as an offset from March 21; unlike easter_date() it does not
        // depend on the system time zone, which shifted the date west of UTC
        $easterSunday = (new DateTimeImmutable(sprintf('%04d-03-21', $year)))
            ->modify(sprintf('+%d days', easter_days($year)));

        // List of dynamic dates
        $days = [];
        $days[self::EASTER_FRIDAY] = $easterSunday->modify('-2 days')->format('Y-m-d');
        $days[self::EASTER_SUNDAY] = $easterSunday->format('Y-m-d');
        $days[self::EASTER_MONDAY] = $easterSunday->modify('+1 day')->format('Y-m-d');

        return $days;
    }

    /**
     * @return HolidayPattern|null
     */
    protected function loadPattern(string $code): ?array
    {
        if (!isset(static::$days[$code])) {
            if (!preg_match('/^[A-Z]{2}$/D', $code)) {
                return null;
            }

            $file = __DIR__ . '/Holiday/Pattern/' . $code . '.php';
            if (!file_exists($file)) {
                return null;
            }

            static::$days[$code] = include $file;
        }

        return static::$days[$code];
    }

    /**
     * Set country code - ISO 3166
     */
    public function setCountry(string $country): self
    {
        $this->country = strtoupper($country);

        return $this;
    }

    /**
     * Get country code - ISO 3166
     */
    public function getCountry(): ?string
    {
        if (empty($this->country)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    '%s expects a "country" option; none given',
                    self::class,
                ),
            );
        }

        return $this->country;
    }

    /**
     * Get a list of holidays
     *
     * @return array<string, string>
     *
     * @throws DateMalformedStringException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\ParseException
     */
    public function getList(): array
    {
        return $this->createList((int) date('Y'));
    }

    /**
     * Get a list of holidays by year
     *
     * @return array<string, string>
     *
     * @throws DateMalformedStringException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\ParseException
     */
    public function getListForYear(int $year): array
    {
        return $this->createList($year);
    }
}

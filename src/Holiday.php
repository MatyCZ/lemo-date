<?php

namespace Lemo\Date;

use DateInterval;
use DateTime;
use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function array_key_exists;
use function date;
use function easter_date;
use function file_exists;
use function ksort;
use function preg_match;
use function sprintf;
use function strtoupper;

class Holiday
{
    public const EASTERFRIDAY = 'easterFriday';
    public const EASTERMONDAY = 'easterMonday';
    public const EASTERSUNDAY = 'easterSunday';

    /**
     * ISO 3611 Country Code
     */
    protected ?string $country = null;

    /**
     * Day patterns
     */
    protected static array $days = [];

    /**
     * Constructor
     *
     * Options
     * - country | string | field or value
     *
     * @param array|Traversable|null $options
     */
    public function __construct(array|Traversable|null $options = null)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (null !== $options) {
            if (array_key_exists('country', $options)) {
                $this->setCountry($options['country']);
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function createList(int $year): array
    {
        $country = $this->getCountry();

        // Load pattern
        $daysPattern = $this->loadPattern($country);

        // Check if patern is loaded
        if (null === $daysPattern) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Pattern file for country '%s' was not found",
                    $country
                )
            );
        }

        // Pattern
        if (empty($daysPattern['dynamic']) || empty($daysPattern['static'])) {
            throw new Exception\ParseException(
                sprintf(
                    "Pattern file for country '%s' has bad format",
                    $country
                )
            );
        }

        $year = $year ?: (int) date('Y');

        $dynamicDates = $this->createDynamicDates($year);
        $holidays = [];

        // Static holidays
        foreach ($daysPattern['static'] as $date => $name) {
            $date = $year . '-' . $date;

            $holidays[$date] = $name;
        }

        // Dynamic holidays
        foreach ($daysPattern['dynamic'] as $pattern => $name) {
            $date = $dynamicDates[$pattern];

            $holidays[$date] = $name;
        }

        ksort($holidays);

        return $holidays;
    }

    /**
     * @throws \Exception
     */
    protected function createDynamicDates(int $year): array
    {
        // Easter - Monday
        $easterSunday = new DateTime(date('Y-m-d', easter_date($year)));

        // Easter - Sunday
        $easterFriday = clone $easterSunday;
        $easterFriday = $easterFriday->sub(new DateInterval('P2D'));

        // Easter - Monday
        $easterMonday = clone $easterSunday;
        $easterMonday = $easterMonday->add(new DateInterval('P1D'));

        // List of dynamic dates
        $days = [];
        $days[self::EASTERFRIDAY] = $easterFriday->format('Y-m-d');
        $days[self::EASTERSUNDAY] = $easterSunday->format('Y-m-d');
        $days[self::EASTERMONDAY] = $easterMonday->format('Y-m-d');

        return $days;
    }

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
                    self::class
                )
            );
        }

        return $this->country;
    }

    /**
     * Get list of holidays
     *
     * @throws \Exception
     */
    public function getList(): array
    {
        return $this->createList((int) date('Y'));
    }

    /**
     * Get list of holidays by year
     *
     * @throws \Exception
     */
    public function getListForYear(int $year): array
    {
        return $this->createList($year);
    }
}

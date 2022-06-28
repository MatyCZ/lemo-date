<?php

namespace Lemo\Date;

use DateTime;
use Lemo\Date\Exception;

class Diff
{
    protected ?DiffInterval $interval = null;

    public function __construct(
        protected DateTime|string|int $dateStart,
        protected DateTime|string|int|null $dateEnd = null,
        protected bool $includeEndDay = false,
        protected bool $includeEveryStarted = false
    ) {
    }

    /**
     * Calculate day difference
     *
     * @throws \Exception
     */
    private function calculate(): void
    {
        if ($this->dateEnd instanceof DateTime) {
            $dateEnd = clone $this->dateEnd;
        } else {
            $dateEnd = new DateTime($this->dateEnd);
        }

        if ($this->dateStart instanceof DateTime) {
            $dateStart = clone $this->dateStart;
        } else {
            $dateStart = new DateTime($this->dateStart);
        }

        if ($dateStart->format('YmdHis') > $dateEnd->format('YmdHis')) {
            throw new Exception\RuntimeException(
                'Start date is greater than end date'
            );
        }

        // Pokud jsou oba datumy shodne, vratime jeden den
        if ($dateStart == $dateEnd) {
            $interval = new DiffInterval();
            $interval->days++;

            $this->interval = $interval;
        } else {
            // Pridame jeden den navic
            if (true === $this->includeEndDay) {
                $dateEnd->modify('+1 day');
            }

            // Calculate date diff
            $diff = $dateEnd->diff($dateStart);

            $interval = new DiffInterval();
            $interval->days = $diff->days;
            $interval->months = ($diff->y * 12) + $diff->m;
            $interval->years = $diff->y;

            if (true === $this->includeEveryStarted) {
                if ($dateStart->format('m') >= $dateEnd->format('m') && $dateStart->format('d') > $dateEnd->format('d')) {
                    $interval->months++;
                }
                if ($dateStart->format('m') > $dateEnd->format('m')) {
                    $interval->years++;
                }
            }

            $this->interval = $interval;
        }
    }

    /**
     * @throws \Exception
     */
    public function getDays(): int
    {
        if (null === $this->interval) {
            $this->calculate();
        }

        return $this->interval->getDays();
    }

    /**
     * @throws \Exception
     */
    public function getMonths(): int
    {
        if (null === $this->interval) {
            $this->calculate();
        }

        return $this->interval->getMonths();
    }

    /**
     * @throws \Exception
     */
    public function getYears(): int
    {
        if (null === $this->interval) {
            $this->calculate();
        }

        return $this->interval->getYears();
    }
}
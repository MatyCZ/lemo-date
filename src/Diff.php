<?php

declare(strict_types=1);

namespace Lemo\Date;

use DateMalformedStringException;
use DateTime;
use DateTimeInterface;

class Diff
{
    protected ?DiffInterval $interval = null;

    public function __construct(
        protected DateTimeInterface|string $dateStart,
        protected DateTimeInterface|string|null $dateEnd = null,
        protected bool $includeEndDay = false,
        protected bool $includeEveryStarted = false,
    ) {}

    /**
     * Calculate day difference
     *
     * @throws Exception\RuntimeException
     * @throws DateMalformedStringException
     */
    private function calculate(): void
    {
        $dateStart = $this->toDateTime($this->dateStart);
        $dateEnd = $this->toDateTime($this->dateEnd);

        if ($dateStart->format('YmdHis') > $dateEnd->format('YmdHis')) {
            throw new Exception\RuntimeException(
                'Start date is greater than end date',
            );
        }

        // Pri zapocteni kazdeho nacateho obdobi je shodne datum jeden nacaty den
        if ($this->includeEveryStarted && $dateStart == $dateEnd) {
            $interval = new DiffInterval();
            $interval->days++;

            $this->interval = $interval;
        } else {
            // Pridame jeden den navic
            if ($this->includeEndDay) {
                $dateEnd->modify('+1 day');
            }

            // Calculate date diff
            $diff = $dateEnd->diff($dateStart);

            $interval = new DiffInterval();
            $interval->days = $diff->days;
            $interval->months = ($diff->y * 12) + $diff->m;
            $interval->years = $diff->y;

            // Kalendarni rozdil, takze se zapocita kazdy nacaty mesic/rok a vysledek
            // nikdy neni mensi nez pocet celych mesicu/let
            if ($this->includeEveryStarted) {
                $years = (int) $dateEnd->format('Y') - (int) $dateStart->format('Y');

                $interval->months = ($years * 12) + (int) $dateEnd->format('n') - (int) $dateStart->format('n');
                $interval->years = $years;
            }

            $this->interval = $interval;
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    private function toDateTime(DateTimeInterface|string|null $date): DateTime
    {
        // Mutable copy, so modify() never touches the caller's object
        if ($date instanceof DateTimeInterface) {
            return DateTime::createFromInterface($date);
        }

        return new DateTime($date ?? 'now');
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getDays(): int
    {
        if (!$this->interval instanceof DiffInterval) {
            $this->calculate();
        }

        return $this->interval->getDays();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getMonths(): int
    {
        if (!$this->interval instanceof DiffInterval) {
            $this->calculate();
        }

        return $this->interval->getMonths();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getYears(): int
    {
        if (!$this->interval instanceof DiffInterval) {
            $this->calculate();
        }

        return $this->interval->getYears();
    }
}

<?php

namespace Lemo\Date;

class DiffInterval
{
    public int $days = 0;
    public int $months = 0;
    public int $years = 0;

    public function getDays(): int
    {
        return $this->days;
    }

    public function getMonths(): int
    {
        return $this->months;
    }

    public function getYears(): int
    {
        return $this->years;
    }
}
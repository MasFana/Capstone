<?php

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

class MenuCalendarContract
{
    public function resolvePackageId(DateTimeImmutable $date): int
    {
        $day = (int) $date->format('j');

        if ($day === 31) {
            return 11;
        }

        if ($date->format('m-d') === '02-29') {
            return 9;
        }

        if ($day >= 1 && $day <= 9) {
            return $day;
        }

        if ($day >= 10 && $day <= 30) {
            return $day % 10 === 0 ? 10 : $day % 10;
        }

        throw new InvalidArgumentException('Unsupported day-of-month for menu package resolution.');
    }
}

<?php

declare(strict_types=1);

namespace UserAccessManager\Util;

use UserAccessManager\Wrapper\Wordpress;

class DateUtil
{
    public function __construct(private Wordpress $wordpress)
    {
    }

    private function formatDateWith(?string $date, string $format): ?string
    {
        return ($date !== null) ? date($format, (int) strtotime($date)) : null;
    }

    public function formatDate(string $date): string
    {
        return $this->wordpress->formatDate($date);
    }

    public function formatDateForDatetimeInput(?string $date): ?string
    {
        return $this->formatDateWith($date, 'Y-m-d\TH:i:s');
    }

    public function formatDateForDateInput(?string $date): ?string
    {
        return $this->formatDateWith($date, 'Y-m-d');
    }

    public function formatDateForTimeInput(?string $date): ?string
    {
        return $this->formatDateWith($date, 'H:i:s');
    }

    public function getDateFromTime(?int $time): ?string
    {
        if ($time === null || $time === 0) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $time + $this->wordpress->currentTime('timestamp'));
    }
}

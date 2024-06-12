<?php

declare(strict_types=1);

namespace UserAccessManager\Util;

use Exception;
use UserAccessManager\Wrapper\Wordpress;

class DateUtil
{
    public function __construct(private Wordpress $wordpress)
    {
    }

    public function formatDate(string $date): string
    {
        return $this->wordpress->formatDate($date);
    }

    /**
     * @throws Exception
     */
    public function formatDateForDatetimeInput(?string $date): ?string
    {
        return ($date !== null) ? date('Y-m-d\TH:i:s', (int) strtotime($date)) : $date;
    }

    /**
     * @throws Exception
     */
    public function formatDateForDateInput(?string $date): ?string
    {
        return ($date !== null) ? date('Y-m-d', (int) strtotime($date)) : $date;
    }

    /**
     * @throws Exception
     */
    public function formatDateForTimeInput(?string $date): ?string
    {
        return ($date !== null) ? date('H:i:s', (int) strtotime($date)) : $date;
    }

    public function getDateFromTime(?int $time): ?string
    {
        if ($time !== null && $time !== 0) {
            $currentTime = $this->wordpress->currentTime('timestamp');
            return gmdate('Y-m-d H:i:s', $time + $currentTime);
        }

        return null;
    }
}

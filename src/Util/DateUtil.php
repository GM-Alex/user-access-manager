<?php
/**
 * DateUtil.php
 *
 * The DateUtil class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Util;

use UserAccessManager\Wrapper\Wordpress;

/**
 * Class DateUtil
 *
 * @package UserAccessManager\Util
 */
class DateUtil
{
    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * DateUtil constructor.
     * @param Wordpress $wordpress
     */
    public function __construct(Wordpress $wordpress)
    {
        $this->wordpress = $wordpress;
    }

    /**
     * Formats the date to the wordpress default format.
     * @param string $date
     * @return string
     */
    public function formatDate(string $date): string
    {
        return $this->wordpress->formatDate($date);
    }

    /**
     * Formats the date for the datetime input field.
     * @param string|null $date
     * @return string
     */
    public function formatDateForDatetimeInput(?string $date): ?string
    {
        return ($date !== null) ? strftime('%Y-%m-%dT%H:%M:%S', (int) strtotime($date)) : $date;
    }

    /**
     * Formats the date for the datetime input field.
     * @param null|string $date
     * @return string
     */
    public function formatDateForDateInput(?string $date): ?string
    {
        return ($date !== null) ? strftime('%Y-%m-%d', (int) strtotime($date)) : $date;
    }

    /**
     * Formats the date for the datetime input field.
     * @param null|string $date
     * @return string
     */
    public function formatDateForTimeInput(?string $date): ?string
    {
        return ($date !== null) ? strftime('%H:%M:%S', (int) strtotime($date)) : $date;
    }

    /**
     * @param int|null $time
     * @return null|string
     */
    public function getDateFromTime(?int $time): ?string
    {
        if ($time !== null && $time !== 0) {
            $currentTime = $this->wordpress->currentTime('timestamp');
            return gmdate('Y-m-d H:i:s', $time + $currentTime);
        }

        return null;
    }
}

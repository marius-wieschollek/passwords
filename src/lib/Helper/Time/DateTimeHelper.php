<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Time;

use DateTime;
use DateTimeZone;

/**
 * Class DateTimeHelper
 *
 * @package OCA\Passwords\Helper\Time
 */
class DateTimeHelper {

    /**
     * @return int
     */
    public function getTimestamp(): int {
        $dateTime = new DateTime();

        return $dateTime->getTimestamp();
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getInternationalWeek(): int {
        $dateTime = $this->getInternationalDateTime();

        return intval($dateTime->format('W'));
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getInternationalHour(): int {
        $dateTime = $this->getInternationalDateTime();

        return intval($dateTime->format('H'));
    }

    /**
     * @param string $time
     *
     * @return DateTime
     * @throws \Exception
     */
    protected function getInternationalDateTime(string $time = 'now'): DateTime {
        return new DateTime($time, new DateTimeZone('Europe/Berlin'));
    }
}
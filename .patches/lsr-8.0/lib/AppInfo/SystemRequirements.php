<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\AppInfo;

/**
 * Class SystemRequirements
 *
 * @package OCA\Passwords\AppInfo
 */
class SystemRequirements {

    const APP_LSR = true;

    const NC_MINIMUM_ID      = 28;
    const NC_NOTIFICATION_ID = 28;
    const NC_UPGRADE_MINIMUM = '28';

    const PHP_MINIMUM             = '8.0.0';
    const PHP_MINIMUM_ID          = 80000;
    const PHP_NOTIFICATION_ID     = 80000;
    const PHP_UPGRADE_MINIMUM     = '8.2';
    const PHP_UPGRADE_MINIMUM_LSR = '8.0';
}
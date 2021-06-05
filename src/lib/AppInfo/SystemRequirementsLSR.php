<?php
/*
 * @copyright 2020 Passwords App
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

    const APP_BC_BREAK = '2021.1.0';
    const APP_LSR      = true;

    const NC_MINIMUM                     = '20.0.0';
    const NC_MINIMUM_ID                  = 20;
    const NC_DEPRECATION_WARNING_ID      = 21;
    const NC_DEPRECATION_NOTIFICATION_ID = 20;
    const NC_UPGRADE_RECOMMENDATION      = '21';

    const PHP_MINIMUM                     = '7.2.0';
    const PHP_MINIMUM_ID                  = 70200;
    const PHP_DEPRECATION_WARNING_ID      = 70400;
    const PHP_DEPRECATION_NOTIFICATION_ID = 70200;
    const PHP_UPGRADE_RECOMMENDATION      = '8.0.0';
}
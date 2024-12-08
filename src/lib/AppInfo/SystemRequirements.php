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

    /**
     * Users with the LSR version will see a warning in the
     * app settings that developers provide no support for
     * their version.
     */
    const APP_LSR = false;

    /**
     * Users with NC version lower than this will receive an
     * error in the settings that their version is not supported
     */
    const NC_MINIMUM_ID = 30;

    /**
     * Users with NC version lower than this will receive a
     * notification that this is the last version of passwords
     * for their version of Nextcloud
     */
    const NC_NOTIFICATION_ID = 30;

    /**
     * Minimum version of NC that users must upgrade to in order
     * to get updates again.
     */
    const NC_UPGRADE_MINIMUM = '30';

    /**
     * The PHP minimum version to be able to install this release
     */
    const PHP_MINIMUM    = '8.3';
    const PHP_MINIMUM_ID = 80300;

    /**
     * Users with a PHP version lower than this will receive a
     * notification that this is the last version of passwords
     * for their version of Nextcloud
     */
    const PHP_NOTIFICATION_ID = 80300;

    /**
     * Minimum version of PHP that users must upgrade to
     * in order to get regular updates again.
     */
    const PHP_UPGRADE_MINIMUM = '8.3';

    /**
     * Minimum version of PHP that users must upgrade to in order
     * to get LSR updates again.
     */
    const PHP_UPGRADE_MINIMUM_LSR = '8.1';
}
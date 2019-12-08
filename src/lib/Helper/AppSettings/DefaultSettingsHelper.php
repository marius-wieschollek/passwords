<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\AppSettings;

/**
 * Class DefaultSettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class DefaultSettingsHelper extends AbstractSettingsHelper {

    /**
     * @var
     */
    protected $scope = 'default';

    /**
     * @var array
     */
    protected $keys
        = [
            'mail.security' => 'settings/mail/security',
            'mail.shares'   => 'settings/mail/shares'
        ];

    /**
     * @var array
     */
    protected $types
        = [
            'mail.security' => 'boolean',
            'mail.shares'   => 'boolean'
        ];

    /**
     * @var array
     */
    protected $defaults
        = [
            'mail.security' => true,
            'mail.shares'   => false
        ];
}
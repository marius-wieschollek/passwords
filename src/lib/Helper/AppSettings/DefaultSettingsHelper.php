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
     * @var string
     */
    protected string $scope = 'default';

    /**
     * @var array
     */
    protected array $keys
        = [
            'mail.security' => 'settings/mail/security',
            'mail.shares'   => 'settings/mail/shares'
        ];

    /**
     * @var array
     */
    protected array $types
        = [
            'mail.security' => 'boolean',
            'mail.shares'   => 'boolean'
        ];

    /**
     * @var array
     */
    protected array $defaults
        = [
            'mail.security' => true,
            'mail.shares'   => false
        ];
}
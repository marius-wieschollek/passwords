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
            'mail.shares'   => 'settings/mail/shares',
            'password.hash' => 'settings/password/security/hash'
        ];

    /**
     * @var array
     */
    protected array $types
        = [
            'mail.security' => 'boolean',
            'mail.shares'   => 'boolean',
            'password.hash' => 'select:number'
        ];

    /**
     * @var array
     */
    protected array $defaults
        = [
            'mail.security' => true,
            'mail.shares'   => false,
            'password.hash' => 40
        ];

    /**
     * @return array
     * @noinspection PhpUnused
     */
    protected function getPasswordHashOptions(): array {
        return [
            $this->generateOptionArray(
                0,
                'Don\'t store hashes'
            ),
            $this->generateOptionArray(
                20,
                'Store 50%% of the hash'
            ),
            $this->generateOptionArray(
                30,
                'Store 75%% of the hash'
            ),
            $this->generateOptionArray(
                40,
                'Store the full hash'
            )
        ];
    }
}
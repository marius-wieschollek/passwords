<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\AppSettings;

class EncryptionSettingsHelper extends AbstractSettingsHelper {

    /**
     * @var string
     */
    protected string $scope = 'encryption';

    /**
     * @var array
     */
    protected array $keys
        = [
            'ssev3.enabled' => 'encryption/SSEv3/enabled'
        ];

    /**
     * @var array
     */
    protected array $types
        = [
            'ssev3.enabled' => 'boolean',
        ];

    /**
     * @var array
     */
    protected array $defaults
        = [
            'ssev3.enabled' => false
        ];
}
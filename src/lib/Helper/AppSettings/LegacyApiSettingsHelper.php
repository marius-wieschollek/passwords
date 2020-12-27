<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\AppSettings;

use OCA\Passwords\Exception\ApiException;

/**
 * Class LegacyApiSettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class LegacyApiSettingsHelper extends AbstractSettingsHelper {

    /**
     * @var string
     */
    protected string $scope = 'legacy';

    /**
     * @var array
     */
    protected array $keys
        = [
            'api.enabled'   => 'legacy_api_enabled',
            'api.last.used' => 'legacy_last_used'
        ];

    /**
     * @var array
     */
    protected array $types
        = [
            'api.enabled'   => 'boolean',
            'api.last.used' => 'number'
        ];

    /**
     * @var array
     */
    protected array $defaults
        = [
            'api.enabled'   => true,
            'api.last.used' => 0
        ];

    /**
     * @param string $key
     * @param        $value
     *
     * @return array
     * @throws ApiException
     */
    public function set(string $key, $value): array {

        if($key === 'api.last.used') {
            throw new ApiException('Setting not writable', 401);
        }

        return parent::set($key, $value);
    }

    /**
     * @param string $key
     *
     * @return array
     * @throws ApiException
     */
    public function reset(string $key): array {

        if($key === 'api.last.used') {
            throw new ApiException('Setting not writable', 401);
        }

        return parent::reset($key);
    }
}
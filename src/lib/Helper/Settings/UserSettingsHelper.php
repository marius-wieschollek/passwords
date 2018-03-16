<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\ConfigurationService;

/**
 * Class UserSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class UserSettingsHelper {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var array
     */
    protected $userSettings
        = [
            'password/generator/strength' => 'integer',
            'password/generator/numbers'  => 'boolean',
            'password/generator/special'  => 'boolean',
            'mail/security'               => 'boolean',
            'mail/shares'                 => 'boolean',
            'notification/security'       => 'boolean',
            'notification/shares'         => 'boolean',
            'notification/errors'         => 'boolean'
        ];

    /**
     * @var array
     */
    protected $userDefaults
        = [
            'password/generator/strength' => 1,
            'password/generator/numbers'  => false,
            'password/generator/special'  => false,
            'mail/security'               => true,
            'mail/shares'                 => false,
            'notification/security'       => true,
            'notification/shares'         => true,
            'notification/errors'         => true
        ];

    /**
     * UserSettingsHelper constructor.
     *
     * @param ConfigurationService $config
     */
    public function __construct(ConfigurationService $config) {
        $this->config = $config;
    }

    /**
     * @param string      $key
     * @param string|null $userId
     *
     * @return null|string
     * @throws ApiException
     */
    public function get(string $key, string $userId = null) {
        $key = str_replace('.', '/', $key);

        if(isset($this->userSettings[ $key ])) {
            $type    = $this->userSettings[ $key ];
            $default = $this->userDefaults[ $key ];
            $value   = $this->config->getUserValue($key, $default, $userId);

            return $this->castValue($type, $value);
        }

        throw new ApiException('Invalid Key', 400);
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws \OCP\PreConditionNotMetException
     * @throws ApiException
     */
    public function set(string $key, $value): void {
        $key = str_replace('.', '/', $key);

        if(isset($this->userSettings[ $key ])) {
            $type  = $this->userSettings[ $key ];
            $value = $this->castValue($type, $value);
            if($type === 'boolean') $value = intval($value);
            $this->config->setUserValue($key, $value);
        } else {
            throw new ApiException('Invalid Key', 400);
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws ApiException
     */
    public function reset(string $key) {
        $key = str_replace('.', '/', $key);

        if(isset($this->userSettings[ $key ])) {
            $this->config->deleteUserValue($key);

            return $this->userDefaults[ $key ];
        }

        throw new ApiException('Invalid Key', 400);
    }

    /**
     * @throws ApiException
     */
    public function list(): array {
        $settings = [];
        foreach(array_keys($this->userSettings) as $key) {
            $setting              = 'user.'.str_replace('/', '.', $key);
            $settings[ $setting ] = $this->get($key);
        }

        return $settings;
    }

    /**
     * @param string $type
     * @param        $value
     *
     * @return bool|float|int|string
     */
    protected function castValue(string $type, $value) {
        if($type === 'integer') {
            return intval($value);
        } else if($type === 'float') {
            return floatval($value);
        } else if($type === 'boolean') {
            return boolval($value);
        }

        return strval($value);
    }
}
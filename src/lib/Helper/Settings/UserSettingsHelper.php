<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

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
            'password/generator/strength'  => 'integer',
            'password/generator/numbers'   => 'boolean',
            'password/generator/special'   => 'boolean',
            'password/security/duplicates' => 'boolean',
            'password/security/age'        => 'integer',
            'mail/security'                => 'boolean',
            'mail/shares'                  => 'boolean',
            'notification/security'        => 'boolean',
            'notification/shares'          => 'boolean',
            'notification/errors'          => 'boolean'
        ];

    /**
     * @var array
     */
    protected $userDefaults
        = [
            'password/generator/strength'  => 1,
            'password/generator/numbers'   => false,
            'password/generator/special'   => false,
            'password/security/duplicates' => true,
            'password/security/age'        => 0,
            'mail/security'                => true,
            'mail/shares'                  => false,
            'notification/security'        => true,
            'notification/shares'          => true,
            'notification/errors'          => true
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
     * @throws \Exception
     */
    public function get(string $key, string $userId = null) {
        $key = str_replace('.', '/', $key);

        if(isset($this->userSettings[ $key ])) {
            $type    = $this->userSettings[ $key ];
            $default = $this->getDefaultValue($key);
            $value   = $this->config->getUserValue($key, $default, $userId);

            return $this->castValue($type, $value);
        }

        return null;
    }

    /**
     * @param string      $key
     * @param             $value
     * @param string|null $userId
     *
     * @return bool|float|int|null|string
     * @throws \Exception
     */
    public function set(string $key, $value, string $userId = null) {
        $key = str_replace('.', '/', $key);

        if(isset($this->userSettings[ $key ])) {
            $type  = $this->userSettings[ $key ];
            $value = $this->castValue($type, $value);

            if($type === 'boolean') {
                $this->config->setUserValue($key, intval($value), $userId);
            } else {
                $this->config->setUserValue($key, $value, $userId);
            }

            return $value;
        }

        return null;
    }

    /**
     * @param string      $key
     * @param string|null $userId
     *
     * @return mixed
     * @throws \Exception
     */
    public function reset(string $key, string $userId = null) {
        $key = str_replace('.', '/', $key);

        if(isset($this->userSettings[ $key ])) {
            $this->config->deleteUserValue($key, $userId);

            return $this->getDefaultValue($key);
        }

        return null;
    }

    /**
     * @param string|null $userId
     *
     * @return array
     * @throws \Exception
     */
    public function list(string $userId = null): array {
        $settings = [];
        foreach(array_keys($this->userSettings) as $key) {
            $setting              = 'user.'.str_replace('/', '.', $key);
            $settings[ $setting ] = $this->get($key, $userId);
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

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getDefaultValue(string $key) {
        $default = $this->userDefaults[ $key ];
        if(in_array($key, ['mail/security', 'mail/shares'])) {
            $default = $this->config->getAppValue('settings/'.$key, $default);
        }

        return $default;
    }

    /**
     * @param string|null $userId
     *
     * @return array
     * @throws \Exception
     */
    public function listRaw(string $userId = null) {
        $settings = [];
        foreach(array_keys($this->userSettings) as $key) {
            $setting              = str_replace('/', '.', $key);
            $settings[ $setting ] = $this->config->getUserValue($key, null, $userId);
        }

        return $settings;
    }
}
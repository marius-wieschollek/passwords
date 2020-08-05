<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use Exception;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\UserChallengeService;

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
            'notification/errors'          => 'boolean',
            'notification/admin'           => 'boolean',
            'session/lifetime'             => 'integer',
            'encryption/sse'               => 'integer',
            'encryption/cse'               => 'integer'
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
            'notification/errors'          => true,
            'notification/admin'           => true,
            'session/lifetime'             => 600,
            'encryption/sse'               => 0
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
            $default = $this->getDefaultValue($key, $userId);
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
            $value = $this->validateValue($key, $value, $userId);

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

            return $this->getDefaultValue($key, $userId);
        }

        return null;
    }

    /**
     * List the settings of the user with the custom value or the default value
     *
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
     * List the settings of the user with the custom value or null if the setting is the default
     *
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
     * @param string      $key
     * @param null|string $userId
     *
     * @return mixed
     */
    protected function getDefaultValue(string $key, ?string $userId) {
        if($key === 'encryption/cse') {
            try {
                return $this->config->hasUserValue(UserChallengeService::USER_CHALLENGE_ID, $userId) ? 1:0;
            } catch(Exception $e) {
                return 0;
            }
        } else if(in_array($key, ['mail/security', 'mail/shares'])) {
            return $this->config->getAppValue('settings/'.$key, $this->userDefaults[ $key ]);
        }

        return $this->userDefaults[ $key ];
    }

    /**
     * @param string      $key
     * @param             $value
     * @param string|null $userId
     *
     * @return int|bool
     */
    protected function validateValue(string $key, $value, string $userId = null) {
        if($key === 'session/lifetime' && $value < 30) {
            return $this->getDefaultValue($key, $userId);
        }
        if($key === 'password/generator/strength' && ($value < 0 || $value > 4)) {
            return $this->getDefaultValue($key, $userId);
        }
        if($key === 'password/security/age' && $value < 0) {
            return $this->getDefaultValue($key, $userId);
        }
        if($key === 'encryption/cse' && ($value < 0 || $value > 1)) {
            return $this->getDefaultValue($key, $userId);
        }
        if($key === 'encryption/sse' && ($value < 0 || $value > 2)) {
            return $this->getDefaultValue($key, $userId);
        }

        return $value;
    }
}
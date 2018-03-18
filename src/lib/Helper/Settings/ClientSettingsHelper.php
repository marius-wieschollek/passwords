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
 * Class ClientSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ClientSettingsHelper {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * ClientSettingsHelper constructor.
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
     * @return null
     */
    public function get(string $key, string $userId = null) {
        $data = json_decode($this->config->getUserValue('client/settings', '{}', $userId), true);
        if(isset($data[ $key ])) {
            return $data[ $key ];
        }

        return null;
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws \OCP\PreConditionNotMetException
     * @throws ApiException
     */
    public function set(string $key, $value): void {
        if(strlen($key) > 48) {
            throw new ApiException('Key too long', 400);
        }
        if(strlen(strval($value)) > 128) {
            throw new ApiException('Value too long', 400);
        }

        $data         = json_decode($this->config->getUserValue('client/settings', '{}'), true);
        $data[ $key ] = $value;
        $this->config->setUserValue('client/settings', json_encode($data));
    }

    /**
     * @param string $key
     *
     * @return null
     * @throws \OCP\PreConditionNotMetException
     */
    public function reset(string $key) {
        $data = json_decode($this->config->getUserValue('client/settings', '{}'), true);
        if(isset($data[ $key ])) {
            unset($data[ $key ]);
            $this->config->setUserValue('client/settings', json_encode($data));
        }

        return null;
    }

    /**
     * @return array
     */
    public function list(): array {
        $settings = [];
        $client = json_decode($this->config->getUserValue('client/settings', '{}'), true);
        foreach($client as $key => $value) {
            $settings[ 'client.'.$key ] = $value;
        }

        return $settings;
    }
}
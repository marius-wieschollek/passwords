<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use Exception;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\ConfigurationService;
use OCP\AppFramework\Http;

/**
 * Class ClientSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ClientSettingsHelper {

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

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
     * @throws Exception
     */
    public function get(string $key, string $userId = null) {
        $data = json_decode($this->config->getUserValue('client/settings', '{}', $userId), true);
        if(isset($data[ $key ])) return $data[ $key ];

        return null;
    }

    /**
     * @param string      $key
     * @param             $value
     * @param string|null $userId
     *
     * @return mixed
     * @throws ApiException
     * @throws Exception
     */
    public function set(string $key, $value, string $userId = null) {
        if(strlen($key) > 48) {
            throw new ApiException('Key too long', Http::STATUS_BAD_REQUEST);
        }
        if(strlen(strval($value)) > 128) {
            throw new ApiException('Value too long', Http::STATUS_BAD_REQUEST);
        }

        $data         = json_decode($this->config->getUserValue('client/settings', '{}', $userId), true);
        $data[ $key ] = $value;
        $this->config->setUserValue('client/settings', json_encode($data), $userId);

        return $value;
    }

    /**
     * @param string      $key
     * @param string|null $userId
     *
     * @return null
     * @throws Exception
     */
    public function reset(string $key, string $userId = null) {
        $data = json_decode($this->config->getUserValue('client/settings', '{}', $userId), true);
        if(isset($data[ $key ])) {
            unset($data[ $key ]);
            $this->config->setUserValue('client/settings', json_encode($data), $userId);
        }

        return null;
    }

    /**
     * @return array
     *
     * @param string|null $userId
     *
     * @throws Exception
     */
    public function list(string $userId = null): array {
        $settings = [];
        $client = json_decode($this->config->getUserValue('client/settings', '{}', $userId), true);
        foreach($client as $key => $value) {
            $settings[ 'client.'.$key ] = $value;
        }

        return $settings;
    }
}
<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Settings\ClientSettingsHelper;
use OCA\Passwords\Helper\Settings\ServerSettingsHelper;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;

/**
 * Class UserSettingsService
 *
 * @package OCA\Passwords\Services
 */
class UserSettingsService {

    /**
     * @var UserSettingsHelper
     */
    protected $userSettings;

    /**
     * @var ClientSettingsHelper
     */
    protected $clientSettings;

    /**
     * @var ServerSettingsHelper
     */
    protected $serverSettings;

    /**
     * UserSettingsService constructor.
     *
     * @param UserSettingsHelper   $userSettings
     * @param ClientSettingsHelper $clientSettings
     * @param ServerSettingsHelper $serverSettings
     */
    public function __construct(
        UserSettingsHelper $userSettings,
        ClientSettingsHelper $clientSettings,
        ServerSettingsHelper $serverSettings
    ) {
        $this->userSettings   = $userSettings;
        $this->serverSettings = $serverSettings;
        $this->clientSettings = $clientSettings;
    }

    /**
     * @param string      $key
     * @param string|null $userId
     *
     * @return mixed
     * @throws \Exception
     */
    public function get(string $key, string $userId = null) {
        list($scope, $subKey) = explode('.', $key, 2);

        switch($scope) {
            case 'user':
                return $this->userSettings->get($subKey, $userId);
            case 'client':
                return $this->clientSettings->get($subKey, $userId);
            case 'server':
                return $this->serverSettings->get($subKey);
        }

        return null;
    }

    /**
     * @param string      $key
     * @param             $value
     * @param string|null $userId
     *
     * @return bool|float|int|mixed|null|string
     * @throws ApiException
     * @throws \Exception
     */
    public function set(string $key, $value, string $userId = null) {
        list($scope, $subKey) = explode('.', $key, 2);

        switch($scope) {
            case 'user':
                return $this->userSettings->set($subKey, $value, $userId);
            case 'client':
                return $this->clientSettings->set($subKey, $value, $userId);
        }

        return null;
    }

    /**
     * @param string      $key
     * @param string|null $userId
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function reset(string $key, string $userId = null) {
        list($scope, $subKey) = explode('.', $key, 2);

        switch($scope) {
            case 'user':
                return $this->userSettings->reset($subKey, $userId);
            case 'client':
                return $this->clientSettings->reset($subKey, $userId);
        }

        return null;
    }

    /**
     * @param array|null  $scope
     * @param string|null $userId
     *
     * @return array
     * @throws \Exception
     */
    public function list(array $scope = null, string $userId = null): array {
        $settings = [];

        if($scope === null || in_array('server', $scope)) {
            $settings = array_merge($settings, $this->serverSettings->list());
        }

        if($scope === null || in_array('user', $scope)) {
            $settings = array_merge($settings, $this->userSettings->list($userId));
        }

        if($scope === null || in_array('client', $scope)) {
            $settings = array_merge($settings, $this->clientSettings->list($userId));
        }

        return $settings;
    }
}
<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\AppSettings\BackupSettingsHelper;
use OCA\Passwords\Helper\AppSettings\DefaultSettingsHelper;
use OCA\Passwords\Helper\AppSettings\EncryptionSettingsHelper;
use OCA\Passwords\Helper\AppSettings\EntitySettingsHelper;
use OCA\Passwords\Helper\AppSettings\NightlySettingsHelper;
use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
use OCA\Passwords\Helper\AppSettings\SurveySettingsHelper;
use OCP\AppFramework\Http;

/**
 * Class AppSettingsService
 *
 * @package OCA\Passwords\Services
 */
class AppSettingsService {

    /**
     * AppSettingsService constructor.
     *
     * @param EntitySettingsHelper     $entitySettings
     * @param BackupSettingsHelper     $backupSettings
     * @param SurveySettingsHelper     $surveySettings
     * @param ServiceSettingsHelper    $serviceSettings
     * @param NightlySettingsHelper    $nightlySettings
     * @param DefaultSettingsHelper    $defaultSettings
     * @param EncryptionSettingsHelper $encryptionSettings
     */
    public function __construct(
        protected EntitySettingsHelper $entitySettings,
        protected BackupSettingsHelper $backupSettings,
        protected SurveySettingsHelper $surveySettings,
        protected ServiceSettingsHelper $serviceSettings,
        protected NightlySettingsHelper $nightlySettings,
        protected DefaultSettingsHelper $defaultSettings,
        protected EncryptionSettingsHelper $encryptionSettings
    ) {
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws ApiException
     */
    public function get(string $key): array {
        [$scope, $subKey] = explode('.', $key, 2);

        switch($scope) {
            case 'entity':
                return $this->entitySettings->get($subKey);
            case 'backup':
                return $this->backupSettings->get($subKey);
            case 'survey':
                return $this->surveySettings->get($subKey);
            case 'service':
                return $this->serviceSettings->get($subKey);
            case 'settings':
                return $this->defaultSettings->get($subKey);
            case 'nightly':
                return $this->nightlySettings->get($subKey);
            case 'encryption':
                return $this->encryptionSettings->get($subKey);
        }

        throw new ApiException('Unknown setting identifier', Http::STATUS_BAD_REQUEST);
    }

    /**
     * @param string      $key
     * @param             $value
     *
     * @return array
     * @throws ApiException
     */
    public function set(string $key, $value): array {
        [$scope, $subKey] = explode('.', $key, 2);

        switch($scope) {
            case 'entity':
                return $this->entitySettings->set($subKey, $value);
            case 'backup':
                return $this->backupSettings->set($subKey, $value);
            case 'survey':
                return $this->surveySettings->set($subKey, $value);
            case 'service':
                return $this->serviceSettings->set($subKey, $value);
            case 'settings':
                return $this->defaultSettings->set($subKey, $value);
            case 'nightly':
                return $this->nightlySettings->set($subKey, $value);
            case 'encryption':
                return $this->encryptionSettings->set($subKey, $value);
        }

        throw new ApiException('Unknown setting identifier', Http::STATUS_BAD_REQUEST);
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     * @throws ApiException
     */
    public function reset(string $key) {
        [$scope, $subKey] = explode('.', $key, 2);

        switch($scope) {
            case 'entity':
                return $this->entitySettings->reset($subKey);
            case 'backup':
                return $this->backupSettings->reset($subKey);
            case 'survey':
                return $this->surveySettings->reset($subKey);
            case 'service':
                return $this->serviceSettings->reset($subKey);
            case 'settings':
                return $this->defaultSettings->reset($subKey);
            case 'nightly':
                return $this->nightlySettings->reset($subKey);
            case 'encryption':
                return $this->encryptionSettings->reset($subKey);
        }

        throw new ApiException('Unknown setting identifier', Http::STATUS_BAD_REQUEST);
    }

    /**
     * @param array|null $scope
     *
     * @return array
     */
    public function list(array $scope = null): array {
        $settings = [];

        if($scope === null || in_array('entity', $scope)) {
            $settings = array_merge($settings, $this->entitySettings->list());
        }

        if($scope === null || in_array('backup', $scope)) {
            $settings = array_merge($settings, $this->backupSettings->list());
        }

        if($scope === null || in_array('survey', $scope)) {
            $settings = array_merge($settings, $this->surveySettings->list());
        }

        if($scope === null || in_array('service', $scope)) {
            $settings = array_merge($settings, $this->serviceSettings->list());
        }

        if($scope === null || in_array('settings', $scope)) {
            $settings = array_merge($settings, $this->defaultSettings->list());
        }

        if($scope === null || in_array('nightly', $scope)) {
            $settings = array_merge($settings, $this->nightlySettings->list());
        }

        if($scope === null || in_array('encryption', $scope)) {
            $settings = array_merge($settings, $this->encryptionSettings->list());
        }

        return $settings;
    }
}
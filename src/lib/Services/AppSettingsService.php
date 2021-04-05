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
use OCA\Passwords\Helper\AppSettings\EntitySettingsHelper;
use OCA\Passwords\Helper\AppSettings\LegacyApiSettingsHelper;
use OCA\Passwords\Helper\AppSettings\NightlySettingsHelper;
use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
use OCA\Passwords\Helper\AppSettings\SurveySettingsHelper;
use OCA\Passwords\Helper\AppSettings\DomainSettingsHelper;

/**
 * Class AppSettingsService
 *
 * @package OCA\Passwords\Services
 */
class AppSettingsService {

    /**
     * @var EntitySettingsHelper
     */
    protected EntitySettingsHelper $entitySettings;

    /**
     * @var BackupSettingsHelper
     */
    protected BackupSettingsHelper $backupSettings;

    /**
     * @var SurveySettingsHelper
     */
    protected SurveySettingsHelper $surveySettings;

    /**
     * @var ServiceSettingsHelper
     */
    protected ServiceSettingsHelper $serviceSettings;

    /**
     * @var DefaultSettingsHelper
     */
    protected DefaultSettingsHelper $defaultSettings;

    /**
     * @var NightlySettingsHelper
     */
    protected NightlySettingsHelper $nightlySettings;

    /**
     * @var LegacyApiSettingsHelper
     */
    protected LegacyApiSettingsHelper $legacyApiSettings;

    /**
     * @var DomainSettingsHelper
     */
    protected DomainSettingsHelper $domainSettings;

    /**
     * AppSettingsService constructor.
     *
     * @param EntitySettingsHelper    $entitySettingsHelper
     * @param BackupSettingsHelper    $backupSettingsHelper
     * @param SurveySettingsHelper    $surveySettingsHelper
     * @param ServiceSettingsHelper   $serviceSettingsHelper
     * @param NightlySettingsHelper   $nightlySettingsHelper
     * @param DefaultSettingsHelper   $defaultSettingsHelper
     * @param LegacyApiSettingsHelper $legacyApiSettingsHelper
     * @param DomainSettingsHelper    $domainSettingsHelper
     */
    public function __construct(
        EntitySettingsHelper $entitySettingsHelper,
        BackupSettingsHelper $backupSettingsHelper,
        SurveySettingsHelper $surveySettingsHelper,
        ServiceSettingsHelper $serviceSettingsHelper,
        NightlySettingsHelper $nightlySettingsHelper,
        DefaultSettingsHelper $defaultSettingsHelper,
        LegacyApiSettingsHelper $legacyApiSettingsHelper,
        DomainSettingsHelper $domainSettingsHelper
    ) {
        $this->entitySettings    = $entitySettingsHelper;
        $this->backupSettings    = $backupSettingsHelper;
        $this->surveySettings    = $surveySettingsHelper;
        $this->serviceSettings   = $serviceSettingsHelper;
        $this->defaultSettings   = $defaultSettingsHelper;
        $this->nightlySettings   = $nightlySettingsHelper;
        $this->legacyApiSettings = $legacyApiSettingsHelper;
        $this->domainSettings    = $domainSettingsHelper;
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
            case 'legacy':
                return $this->legacyApiSettings->get($subKey);
            case 'domain':
                return $this->domainSettings->get($subKey);
        }

        throw new ApiException('Unknown setting identifier', 400);
    }

    /**
     * @param string      $key
     * @param             $value
     *
     * @return bool|float|int|mixed|null|string
     * @throws ApiException
     */
    public function set(string $key, $value) {
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
            case 'legacy':
                return $this->legacyApiSettings->set($subKey, $value);
            case 'domain':
                return $this->domainSettings->set($subKey, $value);
        }

        throw new ApiException('Unknown setting identifier', 400);
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
            case 'legacy':
                return $this->legacyApiSettings->reset($subKey);
            case 'domain':
                return $this->domainSettings->reset($subKey);
        }

        throw new ApiException('Unknown setting identifier', 400);
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

        if($scope === null || in_array('legacy', $scope)) {
            $settings = array_merge($settings, $this->legacyApiSettings->list());
        }

        if($scope === null || in_array('domain', $scope)) {
            $settings = array_merge($settings, $this->domainSettings->list());
        }

        return $settings;
    }
}
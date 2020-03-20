<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Helper\Settings\ServerSettingsHelper;

/**
 * Class DeferredActivationService
 *
 * @package OCA\Passwords\Services
 */
class DeferredActivationService {

    protected $features = null;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var ServerSettingsHelper
     */
    protected $serverSettings;

    /**
     * @var RequestHelper
     */
    protected $httpRequest;

    /**
     * @var FileCacheService
     */
    protected $fileCache;

    /**
     * DeferredActivationService constructor.
     *
     * @param ConfigurationService $config
     * @param ServerSettingsHelper $serverSettings
     * @param RequestHelper        $httpRequest
     * @param FileCacheService     $fileCache
     */
    public function __construct(ConfigurationService $config, ServerSettingsHelper $serverSettings, RequestHelper $httpRequest, FileCacheService $fileCache) {
        $this->config         = $config;
        $this->serverSettings = $serverSettings;
        $this->httpRequest    = $httpRequest;
        $this->fileCache      = $fileCache->getCacheService();
    }

    /**
     * Check the status of a feature.
     *
     * @param string $id
     * @param bool   $ignoreNightly
     *
     * @return bool
     */
    public function check(string $id, bool $ignoreNightly = false): bool {
        if($this->isServiceDisabled()) return false;
        if(!$ignoreNightly && $this->isNightly()) return true;

        $features = $this->getFeatures();
        if(isset($features[ $id ])) return $features[ $id ] === true;

        return false;
    }

    /**
     * @return array|null
     */
    public function getUpdateInfo(): ?array {
        if($this->isServiceDisabled()) return null;

        $features = $this->fetchFeatures();
        if(isset($features['server']['current'])) {
            return $features['server']['current'];
        }

        return null;
    }

    /**
     * Fetch the feature set for this app
     *
     * @return array
     */
    protected function getFeatures(): array {
        if($this->features !== null) return $this->features;

        $data = $this->fetchFeatures();
        if($data === null) {
            $this->features = [];

            return [];
        }

        $this->features = $this->processFeatures($data);

        return $this->features;
    }

    /**
     * @return array|null
     */
    protected function fetchFeatures(): ?array {
        $data = $this->getFeaturesFromCache();
        if($data !== null) return json_decode($data, true);

        $data = $this->getFeaturesFromRemote();
        if($data !== null) return json_decode($data, true);

        return null;
    }

    /**
     * Process the raw json into the feature set for this app
     *
     * @param $json
     *
     * @return array
     */
    protected function processFeatures($json): array {
        if(!isset($json['server'])) return [];

        $version = $this->config->getAppValue('installed_version');
        if(strpos($version, '-') !== false) $version = substr($version, 0, strpos($version, '-'));

        [$major, $minor] = explode('.', $version);
        $mainVersion = $major.'.'.$minor;
        $appFeatures = $json['server'];

        $features = [];
        if(isset($appFeatures[ $mainVersion ])) {
            $features = $appFeatures[ $mainVersion ];
        }

        if(isset($appFeatures[ $version ])) {
            return array_merge($features, $appFeatures[ $version ]);
        }

        return $features;
    }

    /**
     * Check if this is a nightly release
     *
     * @return bool
     */
    protected function isNightly(): bool {
        $version = $this->config->getAppValue('installed_version');

        return strpos($version, '-') !== false;
    }

    /**
     * Try to load the deferred-activation.json from cache
     *
     * @return null|string
     */
    protected function getFeaturesFromCache(): ?string {
        $file = $this->fileCache->getFile('deferred-activation.json');

        if($file === null) return null;

        if($file->getMTime() < strtotime('-12 hours')) return null;

        try {
            return $file->getContent();
        } catch(\Throwable $e) {
            return null;
        }
    }

    /**
     * Get current json file from remote server
     *
     * @return null|string
     */
    protected function getFeaturesFromRemote(): ?string {
        $url = $this->serverSettings->get('handbook.url').'_files/deferred-activation.json';
        $this->httpRequest->setUrl($url);
        $data = $this->httpRequest->send();

        if($data !== null) $this->fileCache->putFile('deferred-activation.json', $data);

        return $data;
    }

    /**
     * @return bool
     */
    protected function isServiceDisabled(): bool {
        return $this->config->getAppValue('das/enabled', '1') !== '1' || $this->config->getSystemValue('has_internet_connection', true) === false;
    }
}
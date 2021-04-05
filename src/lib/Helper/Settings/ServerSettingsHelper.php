<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OCA\Passwords\Services\ConfigurationService;
use OCP\IURLGenerator;
use OCP\Util;

/**
 * Class ServerSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ServerSettingsHelper {

    const SERVER_MANUAL_URL = 'https://raw.githubusercontent.com/wiki/marius-wieschollek/passwords/Users/';

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var IURLGenerator
     */
    protected IURLGenerator $urlGenerator;

    /**
     * @var ShareSettingsHelper
     */
    protected ShareSettingsHelper $shareSettings;

    /**
     * @var ThemeSettingsHelper
     */
    protected ThemeSettingsHelper $themeSettings;

    /**
     * @var DomainSettingsHelper
     */
    protected DomainSettingsHelper $domainSettings;

    /**
     * ServerSettingsHelper constructor.
     *
     * @param IURLGenerator        $urlGenerator
     * @param ConfigurationService $config
     * @param ShareSettingsHelper  $shareSettings
     * @param ThemeSettingsHelper  $themeSettings
     * @param DomainSettingsHelper $domainSettings
     */
    public function __construct(
        IURLGenerator $urlGenerator,
        ConfigurationService $config,
        ShareSettingsHelper $shareSettings,
        ThemeSettingsHelper $themeSettings,
        DomainSettingsHelper $domainSettings
    ) {
        $this->urlGenerator  = $urlGenerator;
        $this->shareSettings = $shareSettings;
        $this->themeSettings = $themeSettings;
        $this->config        = $config;
        $this->domainSettings = $domainSettings;
    }

    /**
     * @param string $key
     *
     * @return null|string
     */
    public function get(string $key) {
        if(strpos($key, '.') !== false) {
            [$scope, $subKey] = explode('.', $key, 2);
        } else {
            $scope  = $key;
            $subKey = '';
        }

        switch($scope) {
            case 'version':
                return $this->getServerVersion();
            case 'baseUrl':
                if($subKey === 'webdav') return Util::linkToRemote('webdav');

                return $this->urlGenerator->getBaseUrl();
            case 'performance':
                return $this->getServerPerformanceHint();
            case 'app':
                if($subKey === 'version') return $this->getAppVersion();

                return null;
            case 'theme':
                return $this->themeSettings->get($subKey);
            case 'sharing':
                return $this->shareSettings->get($subKey);
            case 'domain':
                return $this->domainSettings->get($subKey);
            case 'handbook':
                if($subKey !== 'url') return null;
                $handbookUrl = $this->config->getAppValue('handbook/url', self::SERVER_MANUAL_URL);

                return empty($handbookUrl) ? self::SERVER_MANUAL_URL:$handbookUrl;
        }

        return null;
    }

    /**
     * @return array
     */
    public function list(): array {
        return array_merge(
            [
                'server.baseUrl'        => $this->get('baseUrl'),
                'server.baseUrl.webdav' => $this->get('baseUrl.webdav'),
                'server.version'        => $this->get('version'),
                'server.app.version'    => $this->get('app.version'),
                'server.handbook.url'   => $this->get('handbook.url'),
                'server.performance'    => $this->get('performance')
            ],
            $this->themeSettings->list(),
            $this->shareSettings->list(),
            $this->domainSettings->list()
        );
    }

    /**
     * @return string
     */
    protected function getServerVersion(): string {
        $version = $this->config->getSystemValue('version');

        return explode('.', $version, 2)[0];
    }

    /**
     * @return string
     */
    protected function getAppVersion(): string {
        $version = $this->config->getAppValue('installed_version');
        $parts   = explode('.', $version, 3);

        return "{$parts[0]}.{$parts[1]}";
    }

    /**
     * @return int
     */
    protected function getServerPerformanceHint(): int {
        $performance = $this->config->getAppValue('performance', null);
        if($performance === null) {
            return in_array(php_uname('m'), ['amd64', 'x86_64']) ? 5:1;
        }

        $performance = intval($performance);

        return $performance > -1 && $performance < 7 ? $performance:2;
    }
}
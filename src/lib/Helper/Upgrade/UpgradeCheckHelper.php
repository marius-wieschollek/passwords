<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Upgrade;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\DeferredActivationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\IL10N;
use OCP\IURLGenerator;

/**
 * Class UpgradeCheckHelper
 *
 * @package OCA\Passwords\Helper\Upgrade
 */
class UpgradeCheckHelper {

    /**
     * @var DeferredActivationService
     */
    protected $das;

    /**
     * @var IL10N
     */
    protected $lang;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * UpgradeCheckHelper constructor.
     *
     * @param IL10N                     $lang
     * @param IURLGenerator             $urlGenerator
     * @param ConfigurationService      $config
     * @param DeferredActivationService $das
     * @param EnvironmentService        $environment
     */
    public function __construct(
        IL10N $lang,
        IURLGenerator $urlGenerator,
        ConfigurationService $config,
        DeferredActivationService $das,
        EnvironmentService $environment
    ) {
        $this->das          = $das;
        $this->lang         = $lang;
        $this->config       = $config;
        $this->urlGenerator = $urlGenerator;
        $this->environment  = $environment;
    }

    /**
     * @return array
     */
    public function upgradeRequired(): array {
        $info = $this->das->getUpdateInfo();
        if($info === null || !$this->checkAppUpgradeNeeded($info)) return ['app' => ['upgrade' => false], 'platform' => ['upgrade' => false], 'php' => ['upgrade' => false]];

        return [
            'app'       => [
                'upgrade' => true,
                'version' => $info['version'],
                'url'     => $info['url'],
            ],
            'nextcloud' => [
                'upgrade' => version_compare($this->config->getSystemValue('version'), $info['requirements']['nextcloud'], '<'),
                'version' => $info['requirements']['nextcloud']
            ],
            'php'       => [
                'upgrade' => version_compare(PHP_VERSION, $info['requirements']['php'], '<'),
                'version' => $info['requirements']['php']
            ]
        ];
    }

    /**
     * @return array|null
     */
    public function getUpgradeMessage(): ?array {
        if(!\OC_User::isAdminUser($this->environment->getUserId())) return null;
        $info = $this->upgradeRequired();
        if(!$info['app']['upgrade']) return null;

        $message = $this->lang->t('This version of the passwords app is outdated and should be upgraded.');
        $link    = $this->urlGenerator->linkToRouteAbsolute('settings.AppSettings.viewApps', ['category' => 'updates', 'id' => Application::APP_NAME]);

        if($info['nextcloud']['upgrade'] || $info['php']['upgrade']) {
            $message .= ' '.$this->lang->t('This upgrade requires Nextcloud %s and PHP %s.', [$info['nextcloud']['version'], $info['php']['version']]);
            $link    = $info['app']['url'];
        }

        return [
            'title'   => $this->lang->t('App upgrade required'),
            'message' => $message,
            'link'    => $link
        ];
    }

    /**
     * @param array $info
     *
     * @return bool
     */
    protected function checkAppUpgradeNeeded(array $info): bool {
        $installedVersion = $this->config->getAppValue('installed_version');
        $parts            = explode('.', $installedVersion, 3);
        $installedDate    = strtotime("{$parts[0]}-{$parts[1]}-0");

        $parts       = explode('.', $info['version'], 3);
        $currentDate = strtotime("{$parts[0]}-{$parts[1]}-0");

        return (strtotime('-3 months', $currentDate) >= $installedDate);
    }
}
<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
use OCA\Passwords\Helper\User\AdminUserHelper;
use OCA\Passwords\Services\BackgroundJobService;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\NotificationService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class CheckAppSettings
 *
 * @package OCA\Passwords\Migration
 */
class CheckAppSettings implements IRepairStep {

    const APP_BC_BREAK_VERSION          = '2020.1';
    const NEXTCLOUD_MIN_VERSION         = 17;
    const NEXTCLOUD_RECOMMENDED_VERSION = '18';
    const PHP_MIN_VERSION               = 70200;
    const PHP_RECOMMENDED_VERSION       = '7.3.0';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var AdminUserHelper
     */
    protected $adminHelper;

    /**
     * @var NotificationService
     */
    protected $notifications;

    /**
     * @var ServiceSettingsHelper
     */
    protected $serviceSettings;

    /**
     * @var BackgroundJobService
     */
    protected $backgroundJobService;

    /**
     * CheckAppSettings constructor.
     *
     * @param AdminUserHelper       $adminHelper
     * @param ConfigurationService  $config
     * @param NotificationService   $notifications
     * @param ServiceSettingsHelper $serviceSettings
     * @param BackgroundJobService  $backgroundJobService
     */
    public function __construct(
        AdminUserHelper $adminHelper,
        ConfigurationService $config,
        NotificationService $notifications,
        ServiceSettingsHelper $serviceSettings,
        BackgroundJobService $backgroundJobService
    ) {
        $this->config               = $config;
        $this->adminHelper          = $adminHelper;
        $this->notifications        = $notifications;
        $this->serviceSettings      = $serviceSettings;
        $this->backgroundJobService = $backgroundJobService;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName() {
        return 'Check app settings';
    }

    /**
     * Run repair step.
     * Must throw exception on error.
     *
     * @param IOutput $output
     *
     * @throws \Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output) {
        $faviconSetting    = $this->serviceSettings->get('favicon');
        $faviconApiSetting = $this->serviceSettings->get('favicon.api');

        if($faviconSetting['value'] === HelperService::FAVICON_BESTICON) {
            if(strpos($faviconApiSetting['value'], 'passwords-app-favicons.herokuapp.com') !== false) {
                $this->serviceSettings->reset('favicon.api');
            }
        }

        $previewSetting    = $this->serviceSettings->get('preview');
        $previewApiSetting = $this->serviceSettings->get('preview.api');
        if(empty($previewApiSetting['value']) && in_array($previewSetting['value'], $previewApiSetting['depends']['service.preview'])) {
            $this->sendEmptySettingNotification('preview');
        }

        $ncVersion = intval(explode('.', $this->config->getSystemValue('version'), 2)[0]);
        if($ncVersion < self::NEXTCLOUD_MIN_VERSION || PHP_VERSION_ID < self::PHP_MIN_VERSION) {
            $this->sendDeprecatedPlatformNotification();
        }

        if($this->config->getAppValue('nightly/enabled', '0') === '1') {
            $this->backgroundJobService->addNightlyUpdates();
        }
    }

    /**
     * @param string $setting
     */
    protected function sendEmptySettingNotification(string $setting): void {
        foreach($this->adminHelper->getAdmins() as $admin) {
            $this->notifications->sendEmptyRequiredSettingNotification($admin->getUID(), $setting);
        }
    }

    /**
     *
     */
    protected function sendDeprecatedPlatformNotification(): void {
        foreach($this->adminHelper->getAdmins() as $admin) {
            $this->notifications->sendUpgradeRequiredNotification(
                $admin->getUID(),
                self::APP_BC_BREAK_VERSION,
                self::NEXTCLOUD_RECOMMENDED_VERSION,
                self::PHP_RECOMMENDED_VERSION
            );
        }
    }
}
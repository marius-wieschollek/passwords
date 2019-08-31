<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
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

    /**
     * @var ServiceSettingsHelper
     */
    protected $serviceSettings;

    /**
     * @var NotificationService
     */
    protected $notifications;

    /**
     * CheckAppSettings constructor.
     *
     * @param ServiceSettingsHelper $serviceSettings
     * @param NotificationService   $notifications
     */
    public function __construct(ServiceSettingsHelper $serviceSettings, NotificationService $notifications) {
        $this->serviceSettings = $serviceSettings;
        $this->notifications   = $notifications;
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
        $faviconSetting = $this->serviceSettings->get('favicon');
        $faviconApiSetting = $this->serviceSettings->get('favicon.api');

        if($faviconSetting['value'] === HelperService::FAVICON_BESTICON) {
            if(empty($faviconApiSetting['value'])) {
                $this->sendEmptySettingNotification('favicon');
            } else if($faviconApiSetting['isDefault'] || $faviconApiSetting['value'] === $faviconApiSetting['default']) {
                $this->sendBesticonApiNotification();
            }
        }

        $previewSetting = $this->serviceSettings->get('preview');
        $previewApiSetting = $this->serviceSettings->get('preview.api');
        if(empty($previewApiSetting['value']) && in_array($previewSetting['value'], $previewApiSetting['depends']['service.preview'])) {
            $this->sendEmptySettingNotification('preview');
        }
    }

    /**
     * @param string $setting
     */
    protected function sendEmptySettingNotification(string $setting): void {
        $adminGroup = \OC::$server->getGroupManager()->get('admin');
        foreach($adminGroup->getUsers() as $admin) {
            $this->notifications->sendEmptyRequiredSettingNotification($admin->getUID(), $setting);
        }
    }

    /**
     *
     */
    protected function sendBesticonApiNotification(): void {
        $adminGroup = \OC::$server->getGroupManager()->get('admin');
        foreach($adminGroup->getUsers() as $admin) {
            $this->notifications->sendBesticonApiNotification($admin->getUID());
        }
    }
}
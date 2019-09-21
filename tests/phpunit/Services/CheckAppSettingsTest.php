<?php

namespace OCA\Passwords\Migration;

use Exception;
use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\NotificationService;
use OCA\Passwords\Services\ValidationService;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckAppSettingsTest extends TestCase {

    /**
     * @var ValidationService
     */
    protected $checkAppSettings;

    /**
     * @var MockObject|IGroupManager
     */
    protected $groupManager;

    /**
     * @var MockObject|ServiceSettingsHelper
     */
    protected $settingsHelper;

    /**
     * @var MockObject|NotificationService
     */
    protected $notificationService;

    /**
     * @var MockObject|ConfigurationService
     */
    protected $configurationService;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void {
        $this->groupManager         = $this->createMock(IGroupManager::class);
        $this->settingsHelper       = $this->createMock(ServiceSettingsHelper::class);
        $this->notificationService  = $this->createMock(NotificationService::class);
        $this->configurationService = $this->createMock(ConfigurationService::class);
        $this->checkAppSettings     = new CheckAppSettings($this->groupManager, $this->configurationService, $this->notificationService, $this->settingsHelper);
    }

    /**
     *
     */
    public function testGetName(): void {
        $this->assertEquals('Check app settings', $this->checkAppSettings->getName());
    }

    /**
     *
     */
    public function testSendNotificationIfFaviconApiMissing(): void {
        $this->setUpGroupManager();
        $this->notificationService->expects($this->once())->method('sendEmptyRequiredSettingNotification')->with('admin', 'favicon');

        $this->settingsHelper->method('get')->willReturnMap(
            [
                ['favicon', ['value' => HelperService::FAVICON_BESTICON]],
                ['favicon.api', ['value' => '']],
                ['preview', ['value' => '']],
                ['preview.api', ['value' => 'value']],
            ]
        );
        $this->configurationService->method('getSystemValue')->with('version')->willReturn(CheckAppSettings::NEXTCLOUD_RECOMMENDED_VERSION.'.0.0.0');
        try {
            $this->checkAppSettings->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    public function testSendNoNotificationIfFaviconApiPresent(): void {
        $this->setUpGroupManager();
        $this->notificationService->expects($this->never())->method('sendEmptyRequiredSettingNotification');

        $this->settingsHelper->method('get')->willReturnMap(
            [
                ['favicon', ['value' => HelperService::FAVICON_BESTICON]],
                ['favicon.api', ['value' => 'https://api.example.com']],
                ['preview', ['value' => '']],
                ['preview.api', ['value' => 'value']],
            ]
        );
        $this->configurationService->method('getSystemValue')->with('version')->willReturn(CheckAppSettings::NEXTCLOUD_RECOMMENDED_VERSION.'.0.0.0');
        try {
            $this->checkAppSettings->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    public function testSendNoNotificationIfFaviconApiNotRequired(): void {
        $this->setUpGroupManager();
        $this->notificationService->expects($this->never())->method('sendEmptyRequiredSettingNotification');

        $this->settingsHelper->method('get')->willReturnMap(
            [
                ['favicon', ['value' => 'none']],
                ['favicon.api', ['value' => '']],
                ['preview', ['value' => '']],
                ['preview.api', ['value' => 'value']],
            ]
        );
        $this->configurationService->method('getSystemValue')->with('version')->willReturn(CheckAppSettings::NEXTCLOUD_RECOMMENDED_VERSION.'.0.0.0');
        try {
            $this->checkAppSettings->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    public function testSendNotificationIfPreviewApiMissing(): void {
        $this->setUpGroupManager();
        $this->notificationService->expects($this->once())->method('sendEmptyRequiredSettingNotification')->with('admin', 'preview');

        $this->settingsHelper->method('get')->willReturnMap(
            [
                ['favicon', ['value' => 'none']],
                ['favicon.api', ['value' => '']],
                ['preview', ['value' => 'test']],
                ['preview.api', ['value' => '', 'depends' => ['service.preview' => ['test']]]],
            ]
        );
        $this->configurationService->method('getSystemValue')->with('version')->willReturn(CheckAppSettings::NEXTCLOUD_RECOMMENDED_VERSION.'.0.0.0');
        try {
            $this->checkAppSettings->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    public function testSendNoNotificationIfPreviewApiPresent(): void {
        $this->setUpGroupManager();
        $this->notificationService->expects($this->never())->method('sendEmptyRequiredSettingNotification');

        $this->settingsHelper->method('get')->willReturnMap(
            [
                ['favicon', ['value' => 'none']],
                ['favicon.api', ['value' => '']],
                ['preview', ['value' => 'test']],
                ['preview.api', ['value' => 'key', 'depends' => ['service.preview' => ['test']]]],
            ]
        );
        $this->configurationService->method('getSystemValue')->with('version')->willReturn(CheckAppSettings::NEXTCLOUD_RECOMMENDED_VERSION.'.0.0.0');
        try {
            $this->checkAppSettings->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    public function testSendNotificationIfNextcloudOutdated(): void {
        $this->setUpGroupManager();
        $this->notificationService
            ->expects($this->once())
            ->method('sendUpgradeRequiredNotification')
            ->with('admin', CheckAppSettings::APP_BC_BREAK_VERSION, CheckAppSettings::NEXTCLOUD_RECOMMENDED_VERSION, CheckAppSettings::PHP_RECOMMENDED_VERSION);

        $this->settingsHelper->method('get')->willReturnMap(
            [
                ['favicon', ['value' => '']],
                ['favicon.api', ['value' => '']],
                ['preview', ['value' => '']],
                ['preview.api', ['value' => 'none']],
            ]
        );
        $this->configurationService->method('getSystemValue')->with('version')->willReturn('0.0.0.0');
        try {
            $this->checkAppSettings->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    public function testSendNoNotificationIfNextcloudCurrent(): void {
        $this->setUpGroupManager();
        $this->notificationService
            ->expects($this->never())
            ->method('sendUpgradeRequiredNotification');

        $this->settingsHelper->method('get')->willReturnMap(
            [
                ['favicon', ['value' => '']],
                ['favicon.api', ['value' => '']],
                ['preview', ['value' => '']],
                ['preview.api', ['value' => 'none']],
            ]
        );
        $this->configurationService->method('getSystemValue')->with('version')->willReturn(CheckAppSettings::NEXTCLOUD_RECOMMENDED_VERSION.'.0.0.0');
        try {
            $this->checkAppSettings->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    public function testSendNoNotificationIfPreviewApiNotRequired(): void {
        $this->setUpGroupManager();
        $this->notificationService->expects($this->never())->method('sendEmptyRequiredSettingNotification');

        $this->settingsHelper->method('get')->willReturnMap(
            [
                ['favicon', ['value' => 'none']],
                ['favicon.api', ['value' => '']],
                ['preview', ['value' => 'none']],
                ['preview.api', ['value' => '', 'depends' => ['service.preview' => ['test']]]],
            ]
        );
        $this->configurationService->method('getSystemValue')->with('version')->willReturn(CheckAppSettings::NEXTCLOUD_RECOMMENDED_VERSION.'.0.0.0');
        try {
            $this->checkAppSettings->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    protected function setUpGroupManager(): void {
        $admin = $this->createMock(IUser::class);
        $admin->method('getUID')->willReturn('admin');

        $group = $this->createMock(IGroup::class);
        $group->method('getUsers')->willReturn([$admin]);

        $this->groupManager->method('get')->with('admin')->willReturn($group);
    }
}
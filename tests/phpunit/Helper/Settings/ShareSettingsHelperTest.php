<?php
/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\Settings;

use OCA\Passwords\Services\ConfigurationService;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ShareSettingsHelperTest
 *
 * @covers \OCA\Passwords\Helper\Settings\ShareSettingsHelper
 */
class ShareSettingsHelperTest extends TestCase {

    /**
     * @var MockObject|ConfigurationService
     */
    protected $config;

    /**
     * @var MockObject|IManager
     */
    protected $shareManager;

    /**
     * @var ShareSettingsHelper
     */
    protected $shareSettingsHelper;

    protected function setUp(): void {
        $this->config              = $this->createMock(ConfigurationService::class);
        $this->shareManager        = $this->createMock(IManager::class);
        $this->shareSettingsHelper = new ShareSettingsHelper('admin', $this->shareManager, $this->config);
    }

    /**
     * A group share can only be created if group sharing is allowed by the server
     */
    public function testGetTypesIncludesGroupIfGroupSharingIsEnabled(): void {
        $this->shareManager->method('allowGroupSharing')->willReturn(true);

        $this->assertEquals(['user', 'group'], $this->shareSettingsHelper->get('types'));
    }

    public function testGetTypesOmitsGroupIfGroupSharingIsDisabled(): void {
        $this->shareManager->method('allowGroupSharing')->willReturn(false);

        $this->assertEquals(['user'], $this->shareSettingsHelper->get('types'));
    }

    public function testGetGroupsEnabledUsesTheShareManager(): void {
        $this->shareManager->expects($this->once())->method('allowGroupSharing')->willReturn(true);

        $this->assertTrue($this->shareSettingsHelper->get('groups.enabled'));
    }

    public function testListContainsTheGroupSettings(): void {
        $this->shareManager->method('allowGroupSharing')->willReturn(true);
        $this->shareManager->method('shareApiEnabled')->willReturn(true);
        $this->shareManager->method('sharingDisabledForUser')->willReturn(false);
        $this->shareManager->method('allowEnumeration')->willReturn(true);
        $this->config->method('getAppValue')->willReturn('yes');

        $settings = $this->shareSettingsHelper->list();

        $this->assertTrue($settings['server.sharing.enabled']);
        $this->assertTrue($settings['server.sharing.groups.enabled']);
        $this->assertTrue($settings['server.sharing.resharing']);
        $this->assertTrue($settings['server.sharing.autocomplete']);
        $this->assertEquals(['user', 'group'], $settings['server.sharing.types']);
    }

    public function testSharingIsDisabledIfTheUserMayNotShare(): void {
        $this->shareManager->method('shareApiEnabled')->willReturn(true);
        $this->shareManager->method('sharingDisabledForUser')->with('admin')->willReturn(true);

        $this->assertFalse($this->shareSettingsHelper->get('enabled'));
    }

    public function testSharingIsDisabledIfTheShareApiIsDisabled(): void {
        $this->shareManager->method('shareApiEnabled')->willReturn(false);
        $this->shareManager->method('sharingDisabledForUser')->willReturn(false);

        $this->assertFalse($this->shareSettingsHelper->get('enabled'));
    }

    /**
     * Without a user there is no user the sharing could be disabled for
     */
    public function testSharingWithoutAUserOnlyChecksTheShareApi(): void {
        $helper = new ShareSettingsHelper(null, $this->shareManager, $this->config);
        $this->shareManager->method('shareApiEnabled')->willReturn(true);
        $this->shareManager->expects($this->never())->method('sharingDisabledForUser');

        $this->assertTrue($helper->get('enabled'));
    }

    public function testResharingFollowsTheNextcloudSetting(): void {
        $this->config->method('getAppValue')->with('shareapi_allow_resharing', 'yes', 'core')->willReturn('no');

        $this->assertFalse($this->shareSettingsHelper->get('resharing'));
    }
}

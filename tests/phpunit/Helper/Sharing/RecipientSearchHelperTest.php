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

namespace OCA\Passwords\Helper\Sharing;

use OCA\Passwords\Helper\Settings\ShareSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class RecipientSearchHelperTest
 *
 * Covers the permission check for group shares. It is the only place where the
 * permissions of a group share are checked, the cron job expands them with
 * system privileges afterwards.
 *
 * @covers \OCA\Passwords\Helper\Sharing\RecipientSearchHelper
 */
class RecipientSearchHelperTest extends TestCase {

    /**
     * @var MockObject|IGroupManager
     */
    protected $groupManager;

    /**
     * @var MockObject|ShareSettingsHelper
     */
    protected $shareSettings;

    /**
     * @var MockObject|EnvironmentService
     */
    protected $environment;

    /**
     * @var RecipientSearchHelper
     */
    protected $recipientSearchHelper;

    protected function setUp(): void {
        $this->groupManager  = $this->createMock(IGroupManager::class);
        $this->shareSettings = $this->createMock(ShareSettingsHelper::class);
        $this->environment   = $this->createMock(EnvironmentService::class);

        $this->environment->method('getUserId')->willReturn('max');

        $this->recipientSearchHelper = new RecipientSearchHelper(
            $this->createMock(IManager::class),
            $this->createMock(IUserManager::class),
            $this->groupManager,
            $this->createMock(ConfigurationService::class),
            $this->environment,
            $this->shareSettings
        );
    }

    public function testCanShareWithAGroupTheUserIsMemberOf(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(true);
        $this->groupManager->method('get')->with('developers')->willReturn($this->createMock(IGroup::class));
        $this->groupManager->method('isInGroup')->with('max', 'developers')->willReturn(true);

        $this->assertTrue($this->recipientSearchHelper->canShareWithGroup('developers'));
    }

    /**
     * The membership of the group which was actually requested has to be checked,
     * being in any other group is not enough
     */
    public function testChecksTheMembershipOfTheRequestedGroup(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(true);
        $this->groupManager
             ->method('get')
             ->willReturnCallback(fn($gid) => $gid === 'accounting' ? $this->createMock(IGroup::class):null);
        $this->groupManager
             ->method('isInGroup')
             ->willReturnCallback(fn($uid, $gid) => $uid === 'max' && $gid === 'accounting');

        $this->assertTrue($this->recipientSearchHelper->canShareWithGroup('accounting'));
        $this->assertFalse($this->recipientSearchHelper->canShareWithGroup('developers'));
    }

    /**
     * Sharing with a group the user does not belong to would leak the password
     * to a group of people the user can not even see
     */
    public function testCanNotShareWithAGroupTheUserIsNotMemberOf(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(true);
        $this->groupManager->method('get')->with('developers')->willReturn($this->createMock(IGroup::class));
        $this->groupManager->method('isInGroup')->with('max', 'developers')->willReturn(false);

        $this->assertFalse($this->recipientSearchHelper->canShareWithGroup('developers'));
    }

    public function testCanNotShareWithAGroupIfGroupSharingIsDisabled(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(false);
        $this->groupManager->method('get')->willReturn($this->createMock(IGroup::class));
        $this->groupManager->method('isInGroup')->willReturn(true);

        $this->assertFalse($this->recipientSearchHelper->canShareWithGroup('developers'));
    }

    public function testCanNotShareWithAGroupWhichDoesNotExist(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(true);
        $this->groupManager->method('get')->with('developers')->willReturn(null);
        $this->groupManager->method('isInGroup')->willReturn(true);

        $this->assertFalse($this->recipientSearchHelper->canShareWithGroup('developers'));
    }

    /**
     * Every guest is a member of the pseudo group of the guests app. Sharing with it
     * would share the password with every guest of the server, which is exactly what
     * canShareWithUser() prevents for single users.
     */
    public function testCanNotShareWithTheGuestGroup(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(true);
        $this->groupManager->method('get')->willReturn($this->createMock(IGroup::class));
        $this->groupManager->method('isInGroup')->willReturn(true);

        $this->assertFalse($this->recipientSearchHelper->canShareWithGroup(RecipientSearchHelper::GUEST_GROUP));
    }
}

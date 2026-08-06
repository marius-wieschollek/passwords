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

use OCA\Passwords\Db\Share;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class GroupShareSyncHelperTest
 *
 * @covers \OCA\Passwords\Helper\Sharing\GroupShareSyncHelper
 */
class GroupShareSyncHelperTest extends TestCase {

    /**
     * @var MockObject|ShareService
     */
    protected $shareService;

    /**
     * @var MockObject|IGroupManager
     */
    protected $groupManager;

    /**
     * @var MockObject|LoggingService
     */
    protected $logger;

    /**
     * @var GroupShareSyncHelper
     */
    protected $groupShareSyncHelper;

    protected function setUp(): void {
        $this->shareService = $this->createMock(ShareService::class);
        $this->groupManager = $this->createMock(IGroupManager::class);
        $this->logger       = $this->createMock(LoggingService::class);

        $this->groupShareSyncHelper = new GroupShareSyncHelper(
            $this->shareService,
            $this->groupManager,
            $this->logger
        );
    }

    public function testCreatesSharesForAllGroupMembers(): void {
        $groupShare = $this->makeGroupShare();
        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['max', 'erika']);
        $this->shareService->method('findByParentShare')->willReturn([]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare]);

        $created = [];
        $this->shareService
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(
                function ($passwordId, $receiverId, $type, $editable, $expires, $shareable, $parentShare, $userId) use (&$created) {
                    $created[] = [
                        'password' => $passwordId, 'receiver' => $receiverId, 'type' => $type, 'editable' => $editable,
                        'expires'  => $expires, 'shareable' => $shareable, 'parent' => $parentShare, 'owner' => $userId
                    ];

                    return $this->makeShare($receiverId, 'group-share-uuid');
                }
            );

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(2, $result['created']);
        $this->assertEquals(['max', 'erika'], array_column($created, 'receiver'));

        foreach($created as $share) {
            $this->assertEquals('password-uuid', $share['password']);
            $this->assertEquals(Share::TYPE_USER, $share['type']);
            $this->assertEquals('group-share-uuid', $share['parent']);
            $this->assertEquals('owner', $share['owner']);
            $this->assertTrue($share['editable']);
            $this->assertFalse($share['shareable']);
            $this->assertEquals(1900000000, $share['expires']);
        }
    }

    /**
     * A user can not share with themselves, so the owner is skipped
     */
    public function testSkipsTheOwnerOfTheGroupShare(): void {
        $groupShare = $this->makeGroupShare();
        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['owner', 'max']);
        $this->shareService->method('findByParentShare')->willReturn([]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare]);

        $this->shareService
            ->expects($this->once())
            ->method('create')
            ->with('password-uuid', 'max')
            ->willReturn($this->makeShare('max', 'group-share-uuid'));

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['created']);
    }

    /**
     * A member who already got the password directly must not get a second share
     */
    public function testSkipsMembersWhoAlreadyHaveThePassword(): void {
        $groupShare  = $this->makeGroupShare();
        $directShare = $this->makeShare('max', null);
        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['max', 'erika']);
        $this->shareService->method('findByParentShare')->willReturn([]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare, $directShare]);

        $this->shareService
            ->expects($this->once())
            ->method('create')
            ->with('password-uuid', 'erika')
            ->willReturn($this->makeShare('erika', 'group-share-uuid'));

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['created']);
    }

    public function testDeletesSharesOfFormerGroupMembers(): void {
        $groupShare = $this->makeGroupShare();
        $maxShare   = $this->makeShare('max', 'group-share-uuid');
        $erikaShare = $this->makeShare('erika', 'group-share-uuid');

        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['erika']);
        $this->shareService->method('findByParentShare')->willReturn([$maxShare, $erikaShare]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare, $erikaShare]);

        $this->shareService->expects($this->once())->method('delete')->with($maxShare);
        $this->shareService->expects($this->never())->method('create');

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['deleted']);
        $this->assertEquals(0, $result['created']);
    }

    public function testPropagatesPermissionChangesToTheMemberShares(): void {
        $groupShare = $this->makeGroupShare();
        $maxShare   = $this->makeShare('max', 'group-share-uuid');
        $maxShare->setEditable(false);
        $maxShare->setExpires(1800000000);
        $maxShare->setSourceUpdated(false);

        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['max']);
        $this->shareService->method('findByParentShare')->willReturn([$maxShare]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare, $maxShare]);

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['updated']);
        $this->assertTrue($maxShare->isEditable());
        $this->assertEquals(1900000000, $maxShare->getExpires());
        $this->assertTrue($maxShare->isSourceUpdated(), 'The password of the member has to be updated by the cron job');
    }

    /**
     * Nothing may change when the members and the permissions did not change
     */
    public function testDoesNothingIfTheGroupIsAlreadyInSync(): void {
        $groupShare = $this->makeGroupShare();
        $groupShare->setSourceUpdated(false);
        $maxShare = $this->makeShare('max', 'group-share-uuid');

        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['max']);
        $this->shareService->method('findByParentShare')->willReturn([$maxShare]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare, $maxShare]);

        $this->shareService->expects($this->never())->method('create');
        $this->shareService->expects($this->never())->method('delete');
        $this->shareService->expects($this->never())->method('save');

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(['created' => 0, 'deleted' => 0, 'updated' => 0], $result);
    }

    /**
     * The group share stays, but nothing can be done with a group which no longer exists
     */
    public function testSkipsGroupSharesOfDeletedGroups(): void {
        $groupShare = $this->makeGroupShare();
        $maxShare   = $this->makeShare('max', 'group-share-uuid');
        $this->mockGroupShares([$groupShare]);
        $this->groupManager->method('get')->with('developers')->willReturn(null);
        $this->shareService->method('findByParentShare')->willReturn([$maxShare]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare, $maxShare]);

        $this->shareService->expects($this->never())->method('create');
        // An unresolvable group must not revoke the access of the members, that would destroy their passwords
        $this->shareService->expects($this->never())->method('delete');
        $this->logger->expects($this->once())->method('warning');

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(['created' => 0, 'deleted' => 0, 'updated' => 0], $result);
    }

    /**
     * One broken group share may not stop the others from being synchronized
     */
    public function testContinuesAfterAFailedGroupShare(): void {
        $broken  = $this->makeGroupShare('broken-uuid', 'broken-group');
        $working = $this->makeGroupShare();

        $this->mockGroupShares([$broken, $working]);
        $this->groupManager
            ->method('get')
            ->willReturnCallback(
                function ($gid) {
                    if($gid === 'broken-group') throw new \RuntimeException('group backend is down');

                    return $this->makeGroup(['max']);
                }
            );
        $this->shareService->method('findByParentShare')->willReturn([]);
        $this->shareService->method('findBySourcePassword')->willReturn([]);

        $this->shareService->expects($this->once())->method('create')->willReturn($this->makeShare('max', 'group-share-uuid'));
        $this->logger->expects($this->once())->method('logException');

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['created']);
    }

    /**
     * A user who moves from one group to another keeps the password if both groups
     * have a share of it. The share is handed over to the other group share instead
     * of being deleted, otherwise the password of the user would be deleted as well.
     */
    public function testMovesTheShareOfAMemberToAnotherGroupShareOfTheSamePassword(): void {
        $alphaShare = $this->makeGroupShare('group-share-alpha', 'alpha');
        $betaShare  = $this->makeGroupShare('group-share-beta', 'beta');
        $maxShare   = $this->makeShare('max', 'group-share-beta');

        // the group the member moves to grants different permissions
        $alphaShare->setEditable(false);
        $alphaShare->setShareable(true);
        $alphaShare->setExpires(1800000000);

        $this->mockGroupShares([$alphaShare, $betaShare]);
        // max just joined alpha and left beta
        $this->mockGroupMemberMap(['alpha' => ['max'], 'beta' => []]);
        $this->shareService
            ->method('findByParentShare')
            ->willReturnCallback(fn($uuid) => $uuid === 'group-share-beta' ? [$maxShare]:[]);
        $this->shareService->method('findBySourcePassword')->willReturn([$alphaShare, $betaShare, $maxShare]);

        $this->shareService->expects($this->never())->method('delete');
        $this->shareService->expects($this->never())->method('create');

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(0, $result['deleted'], 'The member is still covered by the other group share');
        $this->assertEquals(0, $result['created'], 'The share of the member is reused, not recreated');
        $this->assertEquals('group-share-alpha', $maxShare->getParentShare());

        // the member now has the permissions of the group they are in now
        $this->assertFalse($maxShare->isEditable());
        $this->assertTrue($maxShare->isShareable());
        $this->assertEquals(1800000000, $maxShare->getExpires());
        $this->assertTrue($maxShare->isSourceUpdated(), 'The password of the member has to be updated by the cron job');
    }

    /**
     * Only a group share of the same password may take over the share of a member
     */
    public function testDoesNotMoveTheShareToAGroupShareOfAnotherPassword(): void {
        $alphaShare = $this->makeGroupShare('group-share-alpha', 'alpha');
        $alphaShare->setSourcePassword('another-password');
        $betaShare = $this->makeGroupShare('group-share-beta', 'beta');
        $maxShare  = $this->makeShare('max', 'group-share-beta');

        $this->mockGroupShares([$alphaShare, $betaShare]);
        $this->mockGroupMemberMap(['alpha' => ['max'], 'beta' => []]);
        $this->shareService
            ->method('findByParentShare')
            ->willReturnCallback(fn($uuid) => $uuid === 'group-share-beta' ? [$maxShare]:[]);
        $this->shareService->method('findBySourcePassword')->willReturn([]);
        $this->shareService->method('create')->willReturn($this->makeShare('max', 'group-share-alpha'));

        $this->shareService->expects($this->once())->method('delete')->with($maxShare);

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['deleted']);
        $this->assertEquals('group-share-beta', $maxShare->getParentShare(), 'The share was not moved');
    }

    /**
     * A group share of another user may not take over the share, the password of that
     * user is a different password even if both were shared with the same group
     */
    public function testDoesNotMoveTheShareToAGroupShareOfAnotherOwner(): void {
        $alphaShare = $this->makeGroupShare('group-share-alpha', 'alpha');
        $alphaShare->setUserId('someone-else');
        $betaShare = $this->makeGroupShare('group-share-beta', 'beta');
        $maxShare  = $this->makeShare('max', 'group-share-beta');

        $this->mockGroupShares([$alphaShare, $betaShare]);
        $this->mockGroupMemberMap(['alpha' => ['max'], 'beta' => []]);
        $this->shareService
            ->method('findByParentShare')
            ->willReturnCallback(fn($uuid) => $uuid === 'group-share-beta' ? [$maxShare]:[]);
        $this->shareService->method('findBySourcePassword')->willReturn([]);
        $this->shareService->method('create')->willReturn($this->makeShare('max', 'group-share-alpha'));

        $this->shareService->expects($this->once())->method('delete')->with($maxShare);

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['deleted']);
        $this->assertEquals('group-share-beta', $maxShare->getParentShare(), 'The share was not moved');
    }

    /**
     * Deleting a group share revokes the password from its members,
     * unless another group share of the same password still contains them
     */
    public function testRemoveGroupShareKeepsMembersCoveredByAnotherGroupShare(): void {
        $alphaShare = $this->makeGroupShare('group-share-alpha', 'alpha');
        $betaShare  = $this->makeGroupShare('group-share-beta', 'beta');
        $maxShare   = $this->makeShare('max', 'group-share-beta');
        $erikaShare = $this->makeShare('erika', 'group-share-beta');

        $this->shareService->method('findAllGroupShares')->willReturn([$alphaShare, $betaShare]);
        $this->mockGroupMemberMap(['alpha' => ['max'], 'beta' => ['max', 'erika']]);
        $this->shareService
            ->method('findByParentShare')
            ->willReturnCallback(fn($uuid) => $uuid === 'group-share-beta' ? [$maxShare, $erikaShare]:[]);

        // erika is only in the deleted group share, max is also in alpha
        $this->shareService->expects($this->once())->method('delete')->with($erikaShare);

        $this->groupShareSyncHelper->removeGroupShare($betaShare);

        $this->assertEquals('group-share-alpha', $maxShare->getParentShare());
    }

    /**
     * If nobody covers the member anymore the share has to go
     */
    public function testDeletesTheShareIfNoOtherGroupShareCoversTheMember(): void {
        $alphaShare = $this->makeGroupShare('group-share-alpha', 'alpha');
        $betaShare  = $this->makeGroupShare('group-share-beta', 'beta');
        $maxShare   = $this->makeShare('max', 'group-share-beta');

        $this->mockGroupShares([$alphaShare, $betaShare]);
        $this->mockGroupMemberMap(['alpha' => [], 'beta' => []]);
        $this->shareService
            ->method('findByParentShare')
            ->willReturnCallback(fn($uuid) => $uuid === 'group-share-beta' ? [$maxShare]:[]);
        $this->shareService->method('findBySourcePassword')->willReturn([$alphaShare, $betaShare]);

        $this->shareService->expects($this->once())->method('delete')->with($maxShare);

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['deleted']);
    }

    /**
     * If the password reached the owner of the group share through other users,
     * sharing it back to them would be undone by the loop detection of the cron
     * job and recreated on every run
     */
    public function testSkipsEveryoneThePasswordWasSharedThroughBefore(): void {
        $groupShare = $this->makeGroupShare();
        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['max', 'erika', 'jane']);
        $this->shareService->method('findByParentShare')->willReturn([]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare]);

        // password-uuid belongs to 'owner' and was shared to them by 'erika', who got it from 'jane'
        $erikaShare = $this->makeShare('owner', null);
        $erikaShare->setUserId('erika');
        $erikaShare->setSourcePassword('erika-password');
        $janeShare = $this->makeShare('erika', null);
        $janeShare->setUserId('jane');
        $janeShare->setSourcePassword('jane-password');

        $this->shareService
            ->method('findByTargetPassword')
            ->willReturnCallback(
                function ($passwordUuid) use ($erikaShare, $janeShare) {
                    if($passwordUuid === 'password-uuid') return $erikaShare;
                    if($passwordUuid === 'erika-password') return $janeShare;

                    throw new DoesNotExistException('no parent share');
                }
            );

        $created = [];
        $this->shareService
            ->method('create')
            ->willReturnCallback(
                function ($passwordId, $receiverId) use (&$created) {
                    $created[] = $receiverId;

                    return $this->makeShare($receiverId, 'group-share-uuid');
                }
            );

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(['max'], $created, 'erika and jane are further up in the share lineage');
        $this->assertEquals(1, $result['created']);
    }

    /**
     * The end of the share lineage is the normal case for a password the owner created
     * themselves. It may not be reported as an error, that would flood the log.
     */
    public function testDoesNotLogTheEndOfTheShareLineage(): void {
        $groupShare = $this->makeGroupShare();
        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['max']);
        $this->shareService->method('findByParentShare')->willReturn([]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare]);
        $this->shareService->method('create')->willReturn($this->makeShare('max', 'group-share-uuid'));
        $this->shareService
             ->method('findByTargetPassword')
             ->willThrowException(new DoesNotExistException('the password was not shared with the owner'));

        $this->logger->expects($this->never())->method('logException');

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['created']);
    }

    /**
     * A real problem while reading the lineage still has to be reported
     */
    public function testLogsAFailureWhileReadingTheShareLineage(): void {
        $groupShare = $this->makeGroupShare();
        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['max']);
        $this->shareService->method('findByParentShare')->willReturn([]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare]);
        $this->shareService->method('create')->willReturn($this->makeShare('max', 'group-share-uuid'));
        $this->shareService
             ->method('findByTargetPassword')
             ->willThrowException(new \RuntimeException('the database is gone'));

        $this->logger->expects($this->once())->method('logException');

        $this->groupShareSyncHelper->synchronize();
    }

    /**
     * The members of a group which can not be resolved are unknown, so their shares
     * are kept instead of deleting the passwords of people who may still be members
     */
    public function testRemoveGroupShareKeepsMembersIfAnotherGroupCanNotBeResolved(): void {
        $alphaShare = $this->makeGroupShare('group-share-alpha', 'alpha');
        $betaShare  = $this->makeGroupShare('group-share-beta', 'beta');
        $maxShare   = $this->makeShare('max', 'group-share-beta');

        $this->shareService->method('findAllGroupShares')->willReturn([$alphaShare, $betaShare]);
        // alpha can not be resolved right now
        $this->mockGroupMemberMap(['beta' => ['max']]);
        $this->shareService
            ->method('findByParentShare')
            ->willReturnCallback(fn($uuid) => $uuid === 'group-share-beta' ? [$maxShare]:[]);

        $this->shareService->expects($this->never())->method('delete');

        $this->groupShareSyncHelper->removeGroupShare($betaShare);

        $this->assertEquals('group-share-alpha', $maxShare->getParentShare(), 'The decision is left to a later run');
    }

    /**
     * A share whose group share is gone would never be looked at again,
     * so the member would keep the password forever
     */
    public function testDeletesSharesWhoseGroupShareNoLongerExists(): void {
        $groupShare  = $this->makeGroupShare();
        $orphanShare = $this->makeShare('max', 'deleted-group-share');

        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', []);
        $this->shareService->method('findByParentShare')->willReturn([]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare]);
        $this->shareService->method('findAllDerivedShares')->willReturn([$orphanShare]);

        $this->shareService->expects($this->once())->method('delete')->with($orphanShare);

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals(1, $result['deleted']);
    }

    /**
     * Unless another group share still covers the member
     */
    public function testKeepsOrphanedSharesWhichAreStillCoveredByAGroupShare(): void {
        $groupShare  = $this->makeGroupShare();
        $orphanShare = $this->makeShare('max', 'deleted-group-share');

        $this->mockGroupShares([$groupShare]);
        $this->mockGroupMembers('developers', ['max']);
        $this->shareService->method('findByParentShare')->willReturn([]);
        $this->shareService->method('findBySourcePassword')->willReturn([$groupShare, $orphanShare]);
        $this->shareService->method('findAllDerivedShares')->willReturn([$orphanShare]);

        $this->shareService->expects($this->never())->method('delete');

        $result = $this->groupShareSyncHelper->synchronize();

        $this->assertEquals('group-share-uuid', $orphanShare->getParentShare());
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * @param Share[] $shares
     */
    protected function mockGroupShares(array $shares): void {
        $this->shareService->method('findAllGroupShares')->willReturn($shares);
    }

    /**
     * @param string   $groupId
     * @param string[] $members
     */
    protected function mockGroupMembers(string $groupId, array $members): void {
        $this->groupManager->method('get')->with($groupId)->willReturn($this->makeGroup($members));
    }

    /**
     * @param array $groups Group id => member uids
     */
    protected function mockGroupMemberMap(array $groups): void {
        $this->groupManager
             ->method('get')
             ->willReturnCallback(
                 fn($gid) => array_key_exists($gid, $groups) ? $this->makeGroup($groups[ $gid ]):null
             );
    }

    /**
     * @param string[] $members
     *
     * @return MockObject|IGroup
     */
    protected function makeGroup(array $members) {
        $users = [];
        foreach($members as $member) {
            $user = $this->createMock(IUser::class);
            $user->method('getUID')->willReturn($member);
            $users[] = $user;
        }

        $group = $this->createMock(IGroup::class);
        $group->method('getUsers')->willReturn($users);

        return $group;
    }

    protected function makeGroupShare(string $uuid = 'group-share-uuid', string $groupId = 'developers'): Share {
        $share = new Share();
        $share->setUuid($uuid);
        $share->setUserId('owner');
        $share->setDeleted(false);
        $share->setType(Share::TYPE_GROUP);
        $share->setReceiver($groupId);
        $share->setParentShare(null);
        $share->setSourcePassword('password-uuid');
        $share->setSourceUpdated(true);
        $share->setTargetUpdated(false);
        $share->setEditable(true);
        $share->setShareable(false);
        $share->setExpires(1900000000);
        $share->setClient('CLIENT::TEST');
        $share->setCreated(1700000000);
        $share->setUpdated(1700000000);

        return $share;
    }

    protected function makeShare(string $receiver, ?string $parentShare): Share {
        $share = new Share();
        $share->setUuid('share-'.$receiver);
        $share->setUserId('owner');
        $share->setDeleted(false);
        $share->setType(Share::TYPE_USER);
        $share->setReceiver($receiver);
        $share->setParentShare($parentShare);
        $share->setSourcePassword('password-uuid');
        $share->setSourceUpdated(false);
        $share->setTargetUpdated(false);
        $share->setEditable(true);
        $share->setShareable(false);
        $share->setExpires(1900000000);
        $share->setClient('CLIENT::TEST');
        $share->setCreated(1700000000);
        $share->setUpdated(1700000000);

        return $share;
    }
}

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

use Exception;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IGroupManager;
use Throwable;

/**
 * Expands group shares into one share per group member and keeps them in sync
 * with the current members of the group.
 *
 * This runs from the cron job without a session user, so no permission checks
 * happen here. Whether the owner may share with the group at all is decided
 * once, when the group share is created, in RecipientSearchHelper::canShareWithGroup().
 *
 * @package OCA\Passwords\Helper\Sharing
 */
class GroupShareSyncHelper {

    /**
     * GroupShareSyncHelper constructor.
     *
     * @param ShareService   $shareService
     * @param IGroupManager  $groupManager
     * @param LoggingService $logger
     */
    public function __construct(
        protected ShareService   $shareService,
        protected IGroupManager  $groupManager,
        protected LoggingService $logger
    ) {
    }

    /**
     * @return array ['created' => int, 'deleted' => int, 'updated' => int]
     * @throws Exception
     */
    public function synchronize(): array {
        $result      = ['created' => 0, 'deleted' => 0, 'updated' => 0];
        $groupShares = $this->shareService->findAllGroupShares();

        // The members of every group are resolved up front. A group which can not be
        // resolved right now has unknown members, removing the shares of all of them
        // would destroy their passwords, so those group shares are left alone.
        $members = [];
        foreach($groupShares as $groupShare) {
            $members[ $groupShare->getUuid() ] = $this->runSafely(
                $groupShare, fn() => $this->getGroupMembers($groupShare)
            );
        }

        // The shares of former members are removed for every group share before any new
        // share is created. Otherwise a member who moved from one group to another could
        // lose their password, because the share of the group they left still looks like
        // an existing share while the group they joined is processed.
        foreach($groupShares as $groupShare) {
            if($members[ $groupShare->getUuid() ] === null) continue;

            $this->runSafely(
                $groupShare, function () use ($groupShare, $groupShares, $members, &$result) {
                $this->removeFormerMembers($groupShare, $groupShares, $members, $result);
            }
            );
        }

        foreach($groupShares as $groupShare) {
            if($members[ $groupShare->getUuid() ] === null) continue;

            $this->runSafely(
                $groupShare, function () use ($groupShare, $members, &$result) {
                $this->addNewMembers($groupShare, $members[ $groupShare->getUuid() ], $result);
            }
            );
        }

        $this->removeOrphanedShares($groupShares, $members, $result);

        return $result;
    }

    /**
     * Removes the shares of members whose group share no longer exists.
     * Only existing group shares are synchronized, so such a share would never be
     * looked at again and the member would keep the password forever.
     *
     * @param Share[] $groupShares
     * @param array   $members
     * @param array   $result
     *
     * @throws Exception
     */
    protected function removeOrphanedShares(array $groupShares, array $members, array &$result): void {
        $knownGroupShares = [];
        foreach($groupShares as $groupShare) {
            $knownGroupShares[ $groupShare->getUuid() ] = true;
        }

        foreach($this->shareService->findAllDerivedShares() as $child) {
            if(isset($knownGroupShares[ $child->getParentShare() ])) continue;

            try {
                $newParent = $this->findCoveringGroupShare($child, $groupShares, $members, $child->getReceiver());

                if($newParent === null) {
                    $this->shareService->delete($child);
                    $result['deleted']++;
                    continue;
                }

                $this->reparentChildShare($newParent, $child);
                $result['updated']++;
            } catch(Throwable $e) {
                $this->logger->logException(
                    $e,
                    [],
                    "Could not clean up the orphaned share {$child->getUuid()}: {$e->getMessage()}"
                );
            }
        }
    }

    /**
     * Revokes the shares which were created for the members of a deleted group share.
     * A member who is also in another group with a share of the same password keeps
     * their share, it is handed over to that group share.
     *
     * @param Share $groupShare
     *
     * @throws Exception
     */
    public function removeGroupShare(Share $groupShare): void {
        $children = $this->shareService->findByParentShare($groupShare->getUuid());
        if(empty($children)) return;

        $groupShares = [];
        $members     = [];
        foreach($this->shareService->findAllGroupShares() as $candidate) {
            if($candidate->getUuid() === $groupShare->getUuid()) continue;
            if($candidate->getSourcePassword() !== $groupShare->getSourcePassword()) continue;

            $groupShares[]                    = $candidate;
            $members[ $candidate->getUuid() ] = $this->runSafely(
                $candidate, fn() => $this->getGroupMembers($candidate)
            );
        }

        // A group which can not be resolved right now may or may not contain the members.
        // Deleting their shares would destroy their passwords, so the decision is left to
        // a later run of the cron job by handing the shares over to that group share.
        $unresolved = $this->findUnresolvedGroupShare($groupShares, $members);

        foreach($children as $child) {
            $newParent = $this->findCoveringGroupShare($groupShare, $groupShares, $members, $child->getReceiver());

            if($newParent === null) {
                if($unresolved === null) {
                    $this->shareService->delete($child);
                    continue;
                }

                $this->logger->warning(
                    "Kept the share {$child->getUuid()} of the deleted group share {$groupShare->getUuid()}, ".
                    "the group of {$unresolved->getUuid()} can not be resolved right now"
                );
                $newParent = $unresolved;
            }

            $this->reparentChildShare($newParent, $child);
        }
    }

    /**
     * @param Share[] $groupShares
     * @param array   $members
     *
     * @return Share|null
     */
    protected function findUnresolvedGroupShare(array $groupShares, array $members): ?Share {
        foreach($groupShares as $candidate) {
            if($members[ $candidate->getUuid() ] === null) return $candidate;
        }

        return null;
    }

    /**
     * @param Share    $groupShare
     * @param callable $callback
     *
     * @return mixed Null if the callback failed
     */
    protected function runSafely(Share $groupShare, callable $callback): mixed {
        try {
            return $callback();
        } catch(Throwable $e) {
            $this->logger->logException(
                $e,
                [],
                "Could not synchronize group share {$groupShare->getUuid()}: {$e->getMessage()}"
            );

            return null;
        }
    }

    /**
     * Removes the shares of users who are no longer in the group.
     * If another group share of the same password still covers the user, the share is
     * moved to that group share instead, so the user keeps the password they already have.
     *
     * @param Share   $groupShare
     * @param Share[] $groupShares
     * @param array   $members
     * @param array   $result
     *
     * @throws Exception
     */
    protected function removeFormerMembers(Share $groupShare, array $groupShares, array $members, array &$result): void {
        foreach($this->shareService->findByParentShare($groupShare->getUuid()) as $child) {
            if(in_array($child->getReceiver(), $members[ $groupShare->getUuid() ], true)) {
                if($this->updateChildShare($groupShare, $child)) $result['updated']++;
                continue;
            }

            $newParent = $this->findCoveringGroupShare($groupShare, $groupShares, $members, $child->getReceiver());
            if($newParent !== null) {
                $this->reparentChildShare($newParent, $child);
                $result['updated']++;
                continue;
            }

            $this->shareService->delete($child);
            $result['deleted']++;
        }
    }

    /**
     * @param Share  $groupShare
     * @param array  $groupMembers
     * @param array  $result
     *
     * @throws Exception
     */
    protected function addNewMembers(Share $groupShare, array $groupMembers, array &$result): void {
        $existingShares = $this->shareService->findBySourcePassword($groupShare->getSourcePassword());

        foreach($groupMembers as $member) {
            if($this->hasShare($existingShares, $groupShare, $member)) continue;

            $existingShares[] = $this->createChildShare($groupShare, $member);
            $result['created']++;
        }

        if($groupShare->isSourceUpdated()) {
            $groupShare->setSourceUpdated(false);
            $this->shareService->save($groupShare);
        }
    }

    /**
     * Another group share of the same password which still contains the user.
     * The share to compare with is either the group share which no longer contains
     * the user, or the share of the member itself. Both have the same password and owner.
     *
     * @param Share   $share
     * @param Share[] $groupShares
     * @param array   $members
     * @param string  $userId
     *
     * @return Share|null
     */
    protected function findCoveringGroupShare(Share $share, array $groupShares, array $members, string $userId): ?Share {
        foreach($groupShares as $candidate) {
            if($candidate->getUuid() === $share->getUuid()) continue;
            if($candidate->getSourcePassword() !== $share->getSourcePassword()) continue;
            if($candidate->getUserId() !== $share->getUserId()) continue;
            if($members[ $candidate->getUuid() ] === null) continue;
            if(!in_array($userId, $members[ $candidate->getUuid() ], true)) continue;

            return $candidate;
        }

        return null;
    }

    /**
     * The members which should receive the password.
     * The owner of the share is skipped, sharing with yourself is not possible.
     * Everyone further up in the share lineage is skipped as well,
     * sharing the password back to them would create a share loop.
     *
     * @param Share $groupShare
     *
     * @return string[]|null Null if the group can not be resolved
     */
    protected function getGroupMembers(Share $groupShare): ?array {
        $group = $this->groupManager->get($groupShare->getReceiver());
        if($group === null) {
            $this->logger->warning(
                "Group {$groupShare->getReceiver()} of share {$groupShare->getUuid()} does not exist"
            );

            return null;
        }

        $excluded = $this->getLineageUserIds($groupShare);

        $members = [];
        foreach($group->getUsers() as $user) {
            if(in_array($user->getUID(), $excluded, true)) continue;
            $members[] = $user->getUID();
        }

        return $members;
    }

    /**
     * The owner of the group share and every user the password was shared through
     * before it reached the owner of the group share.
     * Creating a share for one of them would be deleted again by the share loop
     * detection of the cron job and recreated on every run.
     *
     * @param Share $groupShare
     *
     * @return string[]
     */
    protected function getLineageUserIds(Share $groupShare): array {
        $userIds = [$groupShare->getUserId()];

        $passwordUuid = $groupShare->getSourcePassword();
        $seen         = [];
        while(!empty($passwordUuid) && !isset($seen[ $passwordUuid ])) {
            $seen[ $passwordUuid ] = true;

            try {
                $parentShare = $this->shareService->findByTargetPassword($passwordUuid);
            } catch(DoesNotExistException $e) {
                // The password was not shared with the owner, the chain ends here
                break;
            } catch(Throwable $e) {
                $this->logger->logException(
                    $e,
                    [],
                    "Could not read the share lineage of {$groupShare->getUuid()}: {$e->getMessage()}"
                );
                break;
            }

            $userIds[]    = $parentShare->getUserId();
            $passwordUuid = $parentShare->getSourcePassword();
        }

        // A user id of "0" is falsy, so the filter can not be used without a callback
        return array_values(array_unique(array_filter($userIds, fn($userId) => $userId !== null && $userId !== '')));
    }

    /**
     * Whether the member already has the password, either through a direct share,
     * through this group share or through another group share
     *
     * @param Share[] $existingShares
     * @param Share   $groupShare
     * @param string  $userId
     *
     * @return bool
     */
    protected function hasShare(array $existingShares, Share $groupShare, string $userId): bool {
        foreach($existingShares as $share) {
            if($share->getReceiver() === $userId && $share->getUserId() === $groupShare->getUserId()) return true;
        }

        return false;
    }

    /**
     * @param Share  $groupShare
     * @param string $userId
     *
     * @return Share
     * @throws Exception
     */
    protected function createChildShare(Share $groupShare, string $userId): Share {
        // The cron job has no session user, so the owner has to be passed explicitly
        $share = $this->shareService->create(
            $groupShare->getSourcePassword(),
            $userId,
            Share::TYPE_USER,
            $groupShare->isEditable(),
            $groupShare->getExpires(),
            $groupShare->isShareable(),
            $groupShare->getUuid(),
            $groupShare->getUserId()
        );

        $share->setClient($groupShare->getClient());
        $this->shareService->save($share);

        return $share;
    }

    /**
     * Hand the share of a member over to another group share which still contains them,
     * so that the password of the member survives the group change
     *
     * @param Share $groupShare
     * @param Share $child
     *
     * @throws Exception
     */
    protected function reparentChildShare(Share $groupShare, Share $child): void {
        $child->setParentShare($groupShare->getUuid());
        if($this->copyPermissions($groupShare, $child)) $child->setSourceUpdated(true);

        $this->shareService->save($child);
    }

    /**
     * Copy permission changes of the group share to the share of a group member
     *
     * @param Share $groupShare
     * @param Share $child
     *
     * @return bool
     * @throws Exception
     */
    protected function updateChildShare(Share $groupShare, Share $child): bool {
        if(!$this->copyPermissions($groupShare, $child)) return false;

        $child->setSourceUpdated(true);
        $this->shareService->save($child);

        return true;
    }

    /**
     * Applies the permissions of the group share to the share of a member
     *
     * @param Share $groupShare
     * @param Share $child
     *
     * @return bool Whether anything changed
     */
    protected function copyPermissions(Share $groupShare, Share $child): bool {
        $changed = false;

        if($child->isEditable() !== $groupShare->isEditable()) {
            $child->setEditable($groupShare->isEditable());
            $changed = true;
        }

        if($child->isShareable() !== $groupShare->isShareable()) {
            $child->setShareable($groupShare->isShareable());
            $changed = true;
        }

        if($child->getExpires() !== $groupShare->getExpires()) {
            $child->setExpires($groupShare->getExpires());
            $changed = true;
        }

        return $changed;
    }
}

<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Sharing;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IManager;

/**
 * Class ShareUserListHelper
 *
 * @package OCA\Passwords\Helper\Sharing
 */
class ShareUserListHelper {

    const USER_SEARCH_MINIMUM = 5;
    const USER_SEARCH_LIMIT = 256;

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var IGroupManager
     */
    protected $groupManager;

    /**
     * @var IManager
     */
    protected $shareManager;

    /**
     * ShareUserListHelper constructor.
     *
     * @param IUser         $user
     * @param IManager      $shareManager
     * @param IUserManager  $userManager
     * @param IGroupManager $groupManager
     */
    public function __construct(
        IUser $user,
        IManager $shareManager,
        IUserManager $userManager,
        IGroupManager $groupManager
    ) {
        $this->user         = $user;
        $this->userId       = $user->getUID();
        $this->userManager  = $userManager;
        $this->groupManager = $groupManager;
        $this->shareManager = $shareManager;
    }

    /**
     * @param string $pattern
     * @param int    $limit
     *
     * @return array
     */
    public function getShareUsers(string $pattern = '', int $limit = self::USER_SEARCH_LIMIT): array {
        if(empty($limit) || $limit < self::USER_SEARCH_MINIMUM) $limit = self::USER_SEARCH_MINIMUM;
        if($limit > self::USER_SEARCH_LIMIT) $limit = self::USER_SEARCH_LIMIT;

        if($this->shareManager->shareWithGroupMembersOnly()) return $this->getUsersFromUserGroup($pattern, $limit);

        return $this->getAllUsers($pattern, $limit);
    }

    /**
     * @param string $pattern
     * @param int    $limit
     *
     * @return array
     */
    protected function getUsersFromUserGroup(string $pattern, int $limit): array {
        $partners   = [];
        $userGroups = $this->groupManager->getUserGroupIds($this->user);
        foreach($userGroups as $userGroup) {
            $users = $this->groupManager->displayNamesInGroup($userGroup, $pattern, $limit);
            foreach($users as $uid => $name) {
                if($uid == $this->userId) continue;
                $partners[ $uid ] = $name;
            }
            if(count($partners) >= $limit) break;
        }

        return $partners;
    }

    /**
     * @param string $pattern
     * @param int    $limit
     *
     * @return array
     */
    protected function getAllUsers(string $pattern, int $limit): array {
        $partners = [];
        $usersTmp = $this->userManager->search($pattern, $limit);

        foreach($usersTmp as $user) {
            if($user->getUID() == $this->userId) continue;
            $partners[ $user->getUID() ] = $user->getDisplayName();
        }

        return $partners;
    }

    /**
     * @param string $uid
     *
     * @return bool
     */
    public function canShareWithUser(string $uid): bool {
        if($uid === $this->userId) return false;
        if(!$this->userManager->userExists($uid)) return false;
        if(!$this->shareManager->shareWithGroupMembersOnly()) return true;

        $user       = $this->userManager->get($uid);
        $userGroups = $this->groupManager->getUserGroupIds($this->user);
        foreach($userGroups as $userGroup) {
            if($this->groupManager->get($userGroup)->inGroup($user)) return true;
        }

        return false;
    }
}
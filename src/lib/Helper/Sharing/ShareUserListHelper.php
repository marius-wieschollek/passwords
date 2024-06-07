<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Sharing;

use OC;
use OCA\Guests\UserBackend;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Http;
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
    const USER_SEARCH_LIMIT   = 256;

    /**
     * @var IUser|null
     */
    protected ?IUser $user;

    /**
     * @var string|null
     */
    protected ?string $userId;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var IUserManager
     */
    protected IUserManager $userManager;

    /**
     * @var IGroupManager
     */
    protected IGroupManager $groupManager;

    /**
     * @var IManager
     */
    protected IManager $shareManager;

    /**
     * ShareUserListHelper constructor.
     *
     * @param IManager             $shareManager
     * @param IUserManager         $userManager
     * @param IGroupManager        $groupManager
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     */
    public function __construct(
        IManager $shareManager,
        IUserManager $userManager,
        IGroupManager $groupManager,
        ConfigurationService $config,
        EnvironmentService $environment
    ) {
        $this->user         = $environment->getUser();
        $this->userId       = $environment->getUserId();
        $this->userManager  = $userManager;
        $this->groupManager = $groupManager;
        $this->shareManager = $shareManager;
        $this->config       = $config;
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

        if($this->shareWithGroupMembersOnly()) return $this->getUsersFromUserGroup($pattern, $limit);

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
            if($userGroup === 'guest_app') continue;
            $users = $this->groupManager->displayNamesInGroup($userGroup, $pattern, $limit);
            foreach($users as $uid => $name) {
                if($uid === $this->userId) continue;
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
            if(!$user->isEnabled() || $user->getUID() === $this->userId) continue;
            $partners[ $user->getUID() ] = $user->getDisplayName();
        }

        return $partners;
    }

    /**
     * @param string $receiver
     *
     * @return string
     * @throws ApiException
     */
    public function mapReceiverToUid(string $receiver): string {
        $receiver = trim($receiver);
        if($this->userManager->userExists($receiver)) return $receiver;

        $partners = $this->getShareUsers($receiver);
        if(count($partners) === 1) return array_keys($partners)[0];

        throw new ApiException('Invalid receiver uid', Http::STATUS_BAD_REQUEST);
    }

    /**
     * @param string $uid
     *
     * @return bool
     */
    public function canShareWithUser(string $uid): bool {
        if($uid === $this->userId) return false;
        if(!$this->userManager->userExists($uid)) return false;
        if(!$this->shareWithGroupMembersOnly()) return true;

        $user       = $this->userManager->get($uid);
        $userGroups = $this->groupManager->getUserGroupIds($this->user);
        foreach($userGroups as $userGroup) {
            if($this->groupManager->get($userGroup)->inGroup($user) && $userGroup !== 'guest_app') return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function shareWithGroupMembersOnly(): bool {
        if($this->shareManager->shareWithGroupMembersOnly()) return true;

        if($this->config->isAppEnabled('guests') && $this->config->getAppValue('hide_users', 'true', 'guests') === 'true') {
            // @TODO: Use container instead
            $guestBackend = OC::$server->get(UserBackend::class);

            return $guestBackend->userExists($this->userId);
        }

        return false;
    }
}
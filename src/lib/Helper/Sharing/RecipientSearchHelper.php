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
use OCA\Passwords\Helper\Settings\ShareSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Http;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Share\IManager;

/**
 * Class RecipientSearchHelper
 *
 * @package OCA\Passwords\Helper\Sharing
 */
class RecipientSearchHelper {

    const int USER_SEARCH_MINIMUM = 5;
    const int USER_SEARCH_LIMIT   = 256;

    /**
     * Pseudo group of the guests app. Being in it does not make users colleagues,
     * so it must never be used to find recipients or as a recipient itself.
     */
    const string GUEST_GROUP = 'guest_app';

    /**
     * RecipientSearchHelper constructor.
     *
     * @param IManager             $shareManager
     * @param IUserManager         $userManager
     * @param IGroupManager        $groupManager
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     */
    public function __construct(
        protected IManager             $shareManager,
        protected IUserManager         $userManager,
        protected IGroupManager        $groupManager,
        protected ConfigurationService $config,
        protected EnvironmentService   $environment,
        protected ShareSettingsHelper  $shareSettings,
    ) {
    }

    /**
     * @param string $query
     * @param int    $limit
     * @param bool   $withGroups
     *
     * @return array
     */
    public function findRecipientSuggestions(string $query = '', int $limit = self::USER_SEARCH_LIMIT, bool $withGroups = false): array {
        if(empty($limit) || $limit < self::USER_SEARCH_MINIMUM) $limit = self::USER_SEARCH_MINIMUM;
        if($limit > self::USER_SEARCH_LIMIT) $limit = self::USER_SEARCH_LIMIT;

        if($this->shareWithGroupMembersOnly()) {
            $recipients = $this->findMatchingUsersFromUserGroup($query, $limit);
        } else {
            $recipients = $this->findMatchingUsers($query, $limit);
        }

        if($withGroups) {
            $recipients = [
                ...$recipients,
                ...$this->getMatchingGroups($query, $limit)
            ];

            if(count($recipients) > $limit) {
                $recipients = array_slice($recipients, 0, $limit);
            }
        }

        return $recipients;
    }

    /**
     * @param string $recipient
     *
     * @return string
     * @throws ApiException
     */
    public function mapRecipientToUid(string $recipient): string {
        $recipient = trim($recipient);
        if($this->userManager->userExists($recipient)) return $recipient;

        $recipients = $this->findRecipientSuggestions($recipient);
        if(count($recipients) === 1) return $recipients[0]['id'];

        throw new ApiException('Invalid receiver uid', Http::STATUS_BAD_REQUEST);
    }

    /**
     * @param string $uid
     *
     * @return bool
     */
    public function canShareWithUser(string $uid): bool {
        if($uid === $this->environment->getUserId()) return false;
        if(!$this->userManager->userExists($uid)) return false;
        if(!$this->shareWithGroupMembersOnly()) return true;

        $user       = $this->userManager->get($uid);
        $userGroups = $this->groupManager->getUserGroupIds($this->environment->getUser());
        foreach($userGroups as $userGroup) {
            if($this->groupManager->get($userGroup)->inGroup($user) && $userGroup !== self::GUEST_GROUP) return true;
        }

        return false;
    }

    /**
     * Whether the current user is allowed to share with the given group.
     * This is the only place where group share permissions are checked,
     * the cron job expands group shares with system privileges.
     *
     * @param string $groupId
     *
     * @return bool
     */
    public function canShareWithGroup(string $groupId): bool {
        if(!$this->shareSettings->get('groups.enabled')) return false;
        if($groupId === self::GUEST_GROUP) return false;
        if($this->groupManager->get($groupId) === null) return false;

        return $this->groupManager->isInGroup($this->environment->getUserId(), $groupId);
    }

    /**
     * @param string $groupId
     *
     * @return array
     */
    public function resolveGroup(string $groupId): array {
        if(!$this->shareSettings->get('groups.enabled') || !$this->groupManager->isInGroup($this->environment->getUserId(), $groupId)) {
            return [];
        };

        $group = $this->groupManager->get($groupId);
        $users = [];
        foreach($group->getUsers() as $user) {
            $users[ $user->getUID() ] = $user->getDisplayName();
        }

        return $users;
    }

    /**
     * @param string $query
     * @param int    $limit
     *
     * @return array
     */
    protected function findMatchingUsersFromUserGroup(string $query, int $limit): array {
        $partners     = [];
        $userGroups   = $this->groupManager->getUserGroupIds($this->environment->getUser());
        $autocomplete = $this->shareSettings->get('autocomplete');

        foreach($userGroups as $userGroup) {
            if($userGroup === self::GUEST_GROUP) continue;
            $users     = $this->groupManager->displayNamesInGroup($userGroup, $query, $limit);
            $groupName = $this->groupManager->getDisplayName($userGroup);

            foreach($users as $uid => $name) {
                if($uid === $this->environment->getUserId()) continue;
                if(!$autocomplete && ($uid !== $query && $name !== $query)) continue;

                $partners[ $uid ] = [
                    'id'      => $uid,
                    'name'    => $name,
                    'type'    => 'user',
                    'context' => $groupName
                ];;
            }
            if(count($partners) >= $limit) break;
        }

        return array_values($partners);
    }

    /**
     * @param string $query
     * @param int    $limit
     *
     * @return array
     */
    protected function findMatchingUsers(string $query, int $limit): array {
        $autocomplete  = $this->shareSettings->get('autocomplete');
        $matchingUsers = $this->userManager->searchDisplayName($query, $limit);

        if(str_contains($query, '@')) {
            $matchingUsers = [
                ...$matchingUsers,
                ...$this->userManager->getByEmail($query)
            ];
        }

        $recipients = [];
        foreach($matchingUsers as $user) {
            if(!$user->isEnabled() || $user->getUID() === $this->environment->getUserId()) continue;
            if(!$autocomplete && ($user->getUID() !== $query && $user->getDisplayName() !== $query && $user->getEMailAddress() !== $query)) continue;

            $recipients[ $user->getUID() ] = [
                'id'      => $user->getUID(),
                'name'    => $user->getDisplayName(),
                'type'    => 'user',
                'context' => $user->getEMailAddress()
            ];
        }

        $recipients = array_values($recipients);
        if(count($recipients) > $limit) {
            $recipients = array_slice($recipients, 0, $limit);
        }

        return $recipients;
    }

    /**
     * @param string $query
     * @param int    $limit
     *
     * @return array
     */
    protected function getMatchingGroups(string $query, int $limit): array {
        if(!$this->shareSettings->get('groups.enabled')) {
            return [];
        }

        $autocomplete   = $this->shareSettings->get('autocomplete');
        $matchingGroups = $this->groupManager->search($query, $limit);
        $userGroups     = $this->groupManager->getUserGroupIds($this->environment->getUser());

        $recipients = [];
        foreach($matchingGroups as $group) {
            if($group->getGID() === self::GUEST_GROUP) continue;
            if(!in_array($group->getGID(), $userGroups)) continue;
            if(!$autocomplete && $group->getGID() !== $query && $group->getDisplayName() !== $query) continue;

            $recipients[] = [
                'id'      => $group->getGID(),
                'name'    => $group->getDisplayName(),
                'type'    => 'group',
                'context' => null
            ];
        }

        return $recipients;
    }

    /**
     * @return bool
     */
    protected function shareWithGroupMembersOnly(): bool {
        if($this->shareManager->shareWithGroupMembersOnly()) return true;

        if($this->config->isAppEnabled('guests') && $this->config->getAppValueBool('hide_users', true, 'guests')) {
            // @TODO: Use container instead
            $guestBackend = OC::$server->get(UserBackend::class);

            return $guestBackend->userExists($this->environment->getUserId());
        }

        return false;
    }
}
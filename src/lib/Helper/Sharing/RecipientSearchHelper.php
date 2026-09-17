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

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Settings\ShareSettingsHelper;
use OCA\Passwords\Integrations\GuestsIntegration;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Http;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IManager;

/**
 * Class RecipientSearchHelper
 *
 * @package OCA\Passwords\Helper\Sharing
 */
class RecipientSearchHelper {

    const int USER_SEARCH_MINIMUM    = 5;
    const int USER_SEARCH_LIMIT      = 256;
    const string RECIPIENT_TYPE_USER = 'user';
    const string RECIPIENT_TYPE_GROUP               = 'group';

    /**
     * RecipientSearchHelper constructor.
     *
     * @param IManager           $shareManager
     * @param IUserManager       $userManager
     * @param IGroupManager      $groupManager
     * @param EnvironmentService $environment
     */
    public function __construct(
        protected IManager            $shareManager,
        protected IUserManager        $userManager,
        protected IGroupManager       $groupManager,
        protected EnvironmentService  $environment,
        protected ShareSettingsHelper $shareSettings,
        protected GuestsIntegration   $guestsIntegration
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
     * @return IUser
     * @throws ApiException
     */
    public function mapRecipientToUser(string $recipient): IUser {
        $recipient = trim($recipient);
        if(!$this->userManager->userExists($recipient)) {
            throw new ApiException('Invalid receiver uid', Http::STATUS_BAD_REQUEST);
        }

        $user = $this->userManager->get($recipient);
        if($user->getUID() === $this->environment->getUserId()) {
            throw new ApiException('Invalid receiver uid', Http::STATUS_BAD_REQUEST);
        }

        if(!$this->shareWithGroupMembersOnly()) return $user;

        $userGroups = $this->groupManager->getUserGroupIds($this->environment->getUser());
        if(array_any($userGroups, fn($userGroup) => $this->groupManager->get($userGroup)->inGroup($user) && $userGroup !== 'guest_app')) {
            return $user;
        }

        throw new ApiException('Invalid receiver uid', Http::STATUS_BAD_REQUEST);
    }

    /**
     * @param string $groupId
     *
     * @return array
     */
    public function resolveGroup(string $groupId): array {
        if(!$this->shareSettings->get('groups.enabled') || !$this->groupManager->isInGroup($this->environment->getUserId(), $groupId)) {
            return [];
        }

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
            if($userGroup === 'guest_app') continue;
            $users     = $this->groupManager->displayNamesInGroup($userGroup, $query, $limit);
            $groupName = $this->groupManager->getDisplayName($userGroup);

            foreach($users as $uid => $name) {
                if($uid === $this->environment->getUserId()) continue;
                if(!$autocomplete && ($uid !== $query && $name !== $query)) continue;

                $partners[ $uid ] = [
                    'id'      => $uid,
                    'name'    => $name,
                    'type'    => self::RECIPIENT_TYPE_USER,
                    'context' => $groupName
                ];
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
                'type'    => self::RECIPIENT_TYPE_USER,
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
            if(!in_array($group->getGID(), $userGroups)) continue;
            if(!$autocomplete && $group->getGID() !== $query && $group->getDisplayName() !== $query) continue;

            $recipients[] = [
                'id'      => $group->getGID(),
                'name'    => $group->getDisplayName(),
                'type'    => self::RECIPIENT_TYPE_GROUP,
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

        return $this->guestsIntegration->hasCurrentUserGuestGroupSharingRestriction();
    }
}
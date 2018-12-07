<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Services\ConfigurationService;
use OCP\IUser;

/**
 * Class UserHook
 *
 * @package OCA\Passwords\Hooks
 */
class UserHook {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * UserHook constructor.
     *
     * @param ConfigurationService $config
     */
    public function __construct(ConfigurationService $config) {
        $this->config = $config;
    }

    /**
     * @param string $user
     *
     * @throws \Exception
     */
    public function preCreateUser(string $user): void {
        $deletedUsers = $this->getDeletedUsers();
        if(in_array($user, $deletedUsers)) throw new \InvalidArgumentException("The username {$user} is queued for deletion");
    }

    /**
     * @param IUser $user
     */
    public function postDelete(IUser $user): void {
        $deletedUsers   = $this->getDeletedUsers();
        $deletedUsers[] = $user->getUID();
        $this->config->setAppValue('users/deleted', json_encode($deletedUsers));
    }

    /**
     * @return array
     */
    protected function getDeletedUsers(): array {
        $deletedUsers = json_decode($this->config->getAppValue('users/deleted', '{}'), true);

        return $deletedUsers;
    }
}
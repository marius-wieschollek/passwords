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
     * @param IUser $user
     */
    public function postDelete(IUser $user): void {
        $deletedUsers   = json_decode($this->config->getAppValue('deleted_users', '{}'), true);
        $deletedUsers[] = $user->getUID();
        $this->config->setAppValue('deleted_users', json_encode($deletedUsers));
    }
}
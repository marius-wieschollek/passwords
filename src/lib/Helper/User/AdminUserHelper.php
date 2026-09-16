<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\User;

use OCP\IUser;
use OCP\IGroupManager;

/**
 * Class AdminUserHelper
 *
 * @package OCA\Passwords\Helper\User
 */
class AdminUserHelper {

    /**
     * @var IGroupManager
     */
    protected IGroupManager $groupManager;

    /**
     * @var null|IUser[]
     */
    protected ?array $admins = null;

    /**
     * AdminUserHelper constructor.
     *
     * @param IGroupManager $groupManager
     */
    public function __construct(IGroupManager $groupManager) {
        $this->groupManager = $groupManager;
    }

    /**
     * @return IUser[]
     */
    public function getAdmins(): array {
        if($this->admins === null) {
            $adminGroup   = $this->groupManager->get('admin');
            $this->admins = $adminGroup->getUsers();
        }

        return $this->admins;
    }
}
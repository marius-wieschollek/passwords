<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Class UserService
 *
 * @package OCA\Passwords\Services
 */
class UserService {

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var IL10N
     */
    protected $localisation;

    /**
     * UserService constructor.
     *
     * @param IL10N        $localisation
     * @param IUserManager $userManager
     */
    public function __construct(IL10N $localisation, IUserManager $userManager) {
        $this->localisation = $localisation;
        $this->userManager = $userManager;
    }

    /**
     * @param string $uid
     *
     * @return bool
     */
    public function userExists(string $uid): bool {
        return $this->userManager->userExists($uid);
    }

    /**
     * @param string $uid
     *
     * @return string
     */
    public function getUserName(string $uid): string {
        if(!$this->userExists($uid)) return $this->localisation->t('Deleted User (%s)', [$uid]);

        return $this->userManager->get($uid)->getDisplayName();
    }

    /**
     * @param string $uid
     *
     * @return null|IUser
     */
    public function getUser(string $uid): ?IUser {
        return $this->userManager->get($uid);
    }
}
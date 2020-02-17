<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Hooks;

use Exception;
use InvalidArgumentException;
use OCA\Passwords\Services\BackgroundJobService;
use OCP\IUser;

/**
 * Class UserHook
 *
 * @package OCA\Passwords\Hooks
 */
class UserHook {

    /**
     * @var BackgroundJobService
     */
    protected $backgroundJobService;

    /**
     * UserHook constructor.
     *
     * @param BackgroundJobService $backgroundJobService
     */
    public function __construct(BackgroundJobService $backgroundJobService) {
        $this->backgroundJobService = $backgroundJobService;
    }

    /**
     * @param string $userId
     *
     * @throws Exception
     */
    public function preCreateUser(string $userId): void {
        if($this->backgroundJobService->hasDeleteUserJob($userId)) {
            throw new InvalidArgumentException("The username {$userId} is queued for deletion");
        }
    }

    /**
     * @param IUser $user
     */
    public function postDelete(IUser $user): void {
        $this->backgroundJobService->addDeleteUserJob($user->getUID());
    }
}
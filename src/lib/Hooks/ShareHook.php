<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 06.01.18
 * Time: 14:00
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class ShareHook
 *
 * @package OCA\Passwords\Hooks
 */
class ShareHook {

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * ShareHook constructor.
     *
     * @param ShareService    $shareService
     * @param PasswordService $passwordService
     */
    public function __construct(ShareService $shareService, PasswordService $passwordService) {
        $this->passwordService = $passwordService;
        $this->shareService = $shareService;
    }

    /**
     * @param Share $share
     *
     * @throws MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function postDelete(Share $share) {
        try {
            $shares = $this->shareService->findBySourcePassword($share->getSourcePassword());

            if(empty($shares)) {
                /** @var Password $password */
                $password = $this->passwordService->findByUuid($share->getSourcePassword());
                $password->setHasShares(false);
                $this->passwordService->save($password);
            }
        } catch (DoesNotExistException $e) {}
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 23:23
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\Object\PasswordRevisionService;

/**
 * Class PasswordHook
 *
 * @package OCA\Passwords\Hooks\Password
 */
class PasswordHook {

    /**
     * @var PasswordRevisionService
     */
    protected $revisionService;

    /**
     * PasswordHook constructor.
     *
     * @param PasswordRevisionService $revisionService
     */
    public function __construct(PasswordRevisionService $revisionService) {
        $this->revisionService = $revisionService;
    }

    /**
     * @param Password $password
     *
     * @throws \Exception
     */
    public function postDelete(Password $password): void {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionService->getRevisionsByPassword($password, false);

        foreach ($revisions as $revision) {
            $this->revisionService->deleteRevision($revision);
        }
    }

    /**
     * @param Password $originalPassword
     * @param Password $clonedPassword
     *
     * @throws \Exception
     */
    public function postClone(Password $originalPassword, Password $clonedPassword): void {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionService->getRevisionsByPassword($originalPassword, false);

        foreach ($revisions as $revision) {
            $revisionClone = $this->revisionService->cloneRevision($revision, ['password' => $clonedPassword->getUuid()]);
            $this->revisionService->saveRevision($revisionClone);
            if($revision->getUuid() == $originalPassword->getRevision()) {
                $clonedPassword->setRevision($revisionClone->getUuid());
            }
        }
    }
}
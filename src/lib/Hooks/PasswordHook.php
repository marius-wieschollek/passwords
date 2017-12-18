<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 23:23
 */

namespace OCA\Passwords\Hooks\Password;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Services\Object\PasswordFolderRelationService;
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
     * @var PasswordFolderRelationService
     */
    protected $folderRelationService;

    /**
     * PasswordHook constructor.
     *
     * @param PasswordRevisionService       $revisionService
     * @param PasswordFolderRelationService $folderRelationService
     */
    public function __construct(
        PasswordRevisionService $revisionService,
        PasswordFolderRelationService $folderRelationService
    ) {
        $this->revisionService = $revisionService;
        $this->folderRelationService = $folderRelationService;
    }

    /**
     * @param Password $password
     */
    public function postDelete(Password $password) {
        $this->revisionService->deleteAllRevisionsForPassword($password);
    }

    /**
     * @param Password $original
     * @param Password $clone
     */
    public function postClone(Password $original, Password $clone) {
        $this->revisionService->cloneAllRevisionsForPassword($original, $clone);
    }
}
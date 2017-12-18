<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 14.10.17
 * Time: 13:53
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\Folder;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\PasswordFolderRelationService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;

class FolderHook {

    /**
     * @var FolderRevisionService
     */
    protected $revisionService;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * PasswordHook constructor.
     *
     * @param FolderRevisionService         $revisionService
     * @param PasswordService               $passwordService
     * @param PasswordRevisionService       $passwordRevisionService
     */
    public function __construct(
        FolderRevisionService $revisionService,
        PasswordService $passwordService,
        PasswordRevisionService $passwordRevisionService
    ) {
        $this->revisionService = $revisionService;
        $this->passwordRevisionService = $passwordRevisionService;
        $this->passwordService = $passwordService;
    }

    /**
     * @param Folder $folder
     */
    public function preDelete(Folder $folder) {
        //$folder;

        /**
         * get all password revisions with this folder and if they are the current revision of the password delete them
         */


    }

    /**
     * @param Folder $folder
     */
    public function postDelete(Folder $folder) {
        //$this->revisionService->deleteAllRevisionsForFolder($folder);
    }

    /**
     * @param Folder $original
     * @param Folder $clone
     */
    public function postClone(Folder $original, Folder $clone) {
        //$this->revisionService->cloneAllRevisionsForFolder($original, $clone);
    }
}
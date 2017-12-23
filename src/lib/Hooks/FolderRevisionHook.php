<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 22.12.17
 * Time: 23:18
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;

/**
 * Class FolderRevisionHook
 *
 * @package OCA\Passwords\Hooks
 */
class FolderRevisionHook {

    /**
     * @var FolderRevisionService
     */
    protected $revisionService;

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * FolderHook constructor.
     *
     * @param FolderService           $folderService
     * @param FolderRevisionService   $revisionService
     * @param PasswordService         $passwordService
     * @param PasswordRevisionService $passwordRevisionService
     */
    public function __construct(
        FolderService $folderService,
        FolderRevisionService $revisionService,
        PasswordService $passwordService,
        PasswordRevisionService $passwordRevisionService
    ) {
        $this->revisionService         = $revisionService;
        $this->folderService           = $folderService;
        $this->passwordService         = $passwordService;
        $this->passwordRevisionService = $passwordRevisionService;
    }

    /**
     * @param FolderRevision $originalRevision
     * @param FolderRevision $clonedRevision
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function postClone(FolderRevision $originalRevision, FolderRevision $clonedRevision): void {

        if(!$originalRevision->isTrashed() && $clonedRevision->isTrashed()) {
            $this->updateChildFolders($clonedRevision->getModel());
        } else if($originalRevision->isTrashed() && !$clonedRevision->isTrashed()) {
            $this->updateChildFolders($clonedRevision->getModel(), false);
        }
    }

    /**
     * @param string $folderId
     *
     * @param bool   $suspend
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function updateChildFolders(string $folderId, $suspend = true): void {
        $folders = $this->folderService->getFoldersByParent($folderId);
        foreach ($folders as $folder) {
            if($folder->isSuspended() === $suspend) continue;
            $revision = $this->revisionService->getCurrentRevision($folder, false);
            if($revision->isTrashed()) continue;
            $folder->setSuspended($suspend);
            $this->folderService->saveFolder($folder);
            $this->updateChildFolders($folder->getUuid(), $suspend);
        }
        $this->updateChildPasswords($folderId, $suspend);
    }

    /**
     * @param string $folderId
     * @param bool   $suspend
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function updateChildPasswords(string $folderId, $suspend = true): void {
        $passwords = $this->passwordService->getPasswordsByFolder($folderId);
        foreach ($passwords as $password) {
            if($password->isSuspended() === $suspend) continue;
            $revision = $this->passwordRevisionService->getCurrentRevision($password, false);
            if($revision->isTrashed()) continue;
            $password->setSuspended($suspend);
            $this->passwordService->savePassword($password);
        }
    }
}
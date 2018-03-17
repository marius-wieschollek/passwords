<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;

/**
 * Class FolderHook
 *
 * @package OCA\Passwords\Hooks
 */
class FolderHook {

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
     * @param PasswordService         $passwordService
     * @param FolderRevisionService   $revisionService
     * @param PasswordRevisionService $passwordRevisionService
     */
    public function __construct(
        FolderService $folderService,
        PasswordService $passwordService,
        FolderRevisionService $revisionService,
        PasswordRevisionService $passwordRevisionService
    ) {
        $this->revisionService         = $revisionService;
        $this->folderService           = $folderService;
        $this->passwordService         = $passwordService;
        $this->passwordRevisionService = $passwordRevisionService;
    }

    /**
     * @param Folder         $folder
     * @param FolderRevision $revision
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function preSetRevision(Folder $folder, FolderRevision $revision): void {
        if($folder->getRevision() === null) return;

        $oldRevision = $this->revisionService->findByUuid($folder->getRevision());
        if(!$oldRevision->isTrashed() && $revision->isTrashed()) {
            $this->suspendSubFolders($folder->getUuid());
        } else if($oldRevision->isTrashed() && !$revision->isTrashed()) {
            $this->suspendSubFolders($folder->getUuid(), false);
        }

        $this->checkSuspendedFlag($folder, $revision);
        if($revision->isHidden() && !$oldRevision->isHidden()) {
            $this->hideSubFolders($folder->getUuid());
            $this->hideSubPasswords($folder->getUuid());
        }
    }

    /**
     * @param Folder $folder
     *
     * @throws \Exception
     */
    public function preDelete(Folder $folder): void {
        $folders = $this->folderService->findByParent($folder->getUuid());
        foreach($folders as $subFolder) {
            $this->folderService->delete($subFolder);
        }

        $passwords = $this->passwordService->findByFolder($folder->getUuid());
        foreach($passwords as $password) {
            $this->passwordService->delete($password);
        }
    }

    /**
     * @param Folder $folder
     *
     * @throws \Exception
     */
    public function postDelete(Folder $folder): void {
        /** @var FolderRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($folder->getUuid());

        foreach($revisions as $revision) {
            $this->revisionService->delete($revision);
        }
    }

    /**
     * @param Folder $originalFolder
     * @param Folder $clonedFolder
     *
     * @throws \Exception
     */
    public function postClone(Folder $originalFolder, Folder $clonedFolder): void {
        /** @var FolderRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($originalFolder->getUuid());

        foreach($revisions as $revision) {
            /** @var FolderRevision $clone */
            $clone = $this->revisionService->clone($revision, ['folder' => $clonedFolder->getUuid()]);
            $this->revisionService->save($clone);
            if($revision->getUuid() == $originalFolder->getRevision()) {
                $clonedFolder->setRevision($clone->getUuid());
            }
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
    protected function suspendSubFolders(string $folderId, bool $suspend = true): void {
        $folders = $this->folderService->findByParent($folderId);
        foreach($folders as $folder) {
            if($folder->isSuspended() === $suspend) continue;
            $revision = $this->revisionService->findByUuid($folder->getRevision());
            if($revision->isTrashed()) continue;
            $folder->setSuspended($suspend);
            $this->folderService->save($folder);
            $this->suspendSubFolders($folder->getUuid(), $suspend);
        }
        $this->suspendSubPasswords($folderId, $suspend);
    }

    /**
     * @param string $folderId
     * @param bool   $suspend
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    protected function suspendSubPasswords(string $folderId, bool $suspend = true): void {
        $passwords = $this->passwordService->findByFolder($folderId);
        foreach($passwords as $password) {
            if($password->isSuspended() === $suspend) continue;
            /** @var PasswordRevision $revision */
            $revision = $this->passwordRevisionService->findByUuid($password->getRevision());
            if($revision->isTrashed()) continue;
            $password->setSuspended($suspend);
            $this->passwordService->save($password);
        }
    }

    /**
     * @param string $folderUuid
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function hideSubFolders(string $folderUuid): void {
        $folders = $this->folderService->findByParent($folderUuid);
        foreach($folders as $folder) {
            $folderRevision = $this->revisionService->findByUuid($folder->getRevision());

            if(!$folderRevision->isHidden()) {
                /** @var FolderRevision $clonedRevision */
                $clonedRevision = $this->revisionService->clone($folderRevision, ['hidden' => true]);
                $this->revisionService->save($clonedRevision);
                $this->folderService->setRevision($folder, $clonedRevision);
            }
        }
    }

    /**
     * @param string $folderUuid
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function hideSubPasswords(string $folderUuid): void {
        $passwords = $this->passwordService->findByFolder($folderUuid);
        foreach($passwords as $password) {
            /** @var PasswordRevision $passwordRevision */
            $passwordRevision = $this->passwordRevisionService->findByUuid($password->getRevision());

            if(!$passwordRevision->isHidden()) {
                /** @var PasswordRevision $clonedRevision */
                $clonedRevision = $this->passwordRevisionService->clone($passwordRevision, ['hidden' => true]);
                $this->passwordRevisionService->save($clonedRevision);
                $this->passwordService->setRevision($password, $clonedRevision);
            }
        }
    }

    /**
     * @param Folder         $folder
     * @param FolderRevision $revision
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function checkSuspendedFlag(Folder $folder, FolderRevision $revision): void {
        if($folder->isSuspended()) {
            $parent = $this->folderService->findByUuid($revision->getParent());
            if(!$parent->isSuspended()) {
                $parentRevision = $this->revisionService->findByUuid($parent->getRevision());
                if(!$parentRevision->isTrashed()) {
                    $folder->setSuspended(false);
                    $this->folderService->save($folder);
                }
            }
        }
    }
}
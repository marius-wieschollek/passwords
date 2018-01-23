<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 14.10.17
 * Time: 13:53
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
        $this->revisionService = $revisionService;
        $this->folderService   = $folderService;
        $this->passwordService = $passwordService;
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
        if($revision->isHidden() && !$oldRevision->isHidden()) {
            $folders = $this->folderService->findByParent($folder->getUuid());
            foreach($folders as $subFolder) {
                $folderRevision = $this->revisionService->findByUuid($subFolder->getRevision());

                if(!$folderRevision->isHidden()) {
                    /** @var FolderRevision $clonedRevision */
                    $clonedRevision = $this->revisionService->clone($folderRevision, ['hidden' => true]);
                    $this->revisionService->save($clonedRevision);
                    $this->folderService->setRevision($subFolder, $clonedRevision);
                }
            }

            $passwords = $this->passwordService->findByFolder($folder->getUuid());
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
}
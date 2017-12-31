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
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
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
     * FolderHook constructor.
     *
     * @param FolderService         $folderService
     * @param FolderRevisionService $revisionService
     * @param PasswordService       $passwordService
     */
    public function __construct(
        FolderService $folderService,
        FolderRevisionService $revisionService,
        PasswordService $passwordService
    ) {
        $this->revisionService = $revisionService;
        $this->folderService   = $folderService;
        $this->passwordService = $passwordService;
    }

    /**
     * @param Folder $folder
     *
     * @throws \Exception
     */
    public function preDelete(Folder $folder): void {
        $folders = $this->folderService->findByParent($folder->getUuid());
        foreach ($folders as $folder) {
            $this->folderService->delete($folder);
        }

        $passwords = $this->passwordService->findByFolder($folder->getUuid());
        foreach ($passwords as $password) {
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

        foreach ($revisions as $revision) {
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

        foreach ($revisions as $revision) {
            /** @var FolderRevision $clone */
            $clone = $this->revisionService->clone($revision, ['folder' => $clonedFolder->getUuid()]);
            $this->revisionService->save($clone);
            if($revision->getUuid() == $originalFolder->getRevision()) {
                $clonedFolder->setRevision($clone->getUuid());
            }
        }
    }
}
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
     * @param FolderService $folderService
     * @param FolderRevisionService $revisionService
     * @param PasswordService $passwordService
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
        $folders = $this->folderService->getFoldersByParent($folder->getUuid());
        foreach ($folders as $folder) {
            $this->folderService->deleteFolder($folder);
        }

        $passwords = $this->passwordService->getPasswordsByFolder($folder->getUuid());
        foreach ($passwords as $password) {
            $this->passwordService->deletePassword($password);
        }
    }

    /**
     * @param Folder $folder
     *
     * @throws \Exception
     */
    public function postDelete(Folder $folder): void {
        /** @var FolderRevision[] $revisions */
        $revisions = $this->revisionService->getRevisionsByFolder($folder, false);

        foreach ($revisions as $revision) {
            $this->revisionService->deleteRevision($revision);
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
        $revisions = $this->revisionService->getRevisionsByFolder($originalFolder, false);

        foreach ($revisions as $revision) {
            $clone = $this->revisionService->cloneRevision($revision, ['folder' => $clonedFolder->getUuid()]);
            $this->revisionService->saveRevision($clone);
            if($revision->getUuid() == $originalFolder->getRevision()) {
                $clonedFolder->setRevision($clone->getUuid());
            }
        }
    }
}
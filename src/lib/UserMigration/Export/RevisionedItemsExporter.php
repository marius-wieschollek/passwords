<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\UserMigration\Export;

use OCA\Passwords\Services\Object\AbstractModelService;
use OCA\Passwords\Services\Object\AbstractRevisionService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;

class RevisionedItemsExporter {

    public function __construct(
        protected TagService $tagService,
        protected FolderService $folderService,
        protected PasswordService $passwordService,
        protected TagRevisionService $tagRevisionService,
        protected FolderRevisionService $folderRevisionService,
        protected PasswordRevisionService $passwordRevisionService
    ) {
    }

    public function exportData($userId) {
        $data['passwords'] = $this->getItemData($userId, $this->passwordService, $this->passwordRevisionService);
        $data['folders'] = $this->getItemData($userId, $this->folderService, $this->folderRevisionService);
        $data['tags'] = $this->getItemData($userId, $this->tagService, $this->tagRevisionService);

        return $data;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getItemData($userId, AbstractModelService $modelService, AbstractRevisionService $revisionService): array {
        $rawRevisions = $revisionService->findAll(true, $userId);
        $revisions    = [];
        foreach($rawRevisions as $revision) {
            if(!isset($revisions[ $revision->getModel() ])) {
                $revisions[ $revision->getModel() ] = [];
            }
            $revisions[ $revision->getModel() ][] = $this->revisionToArray($revision);
        }

        $rawModels = $modelService->findAll($userId);
        $data      = [];
        foreach($rawModels as $model) {
            if((method_exists($model, 'getShareId') && $model->getShareId() !== null) || !isset($revisions[ $model->getUuid() ])) {
                continue;
            }

            $data[] = [
                'model'     => $this->modelToArray($model),
                'revisions' => $revisions[ $model->getUuid() ]
            ];
        }

        return $data;
    }

    protected function revisionToArray(\OCA\Passwords\Db\RevisionInterface $revision): array {
        $data = $revision->toArray();

        if(isset($data['sseKey'])) {
            $data['sseKey'] = null;
        }

        return $data;
    }

    protected function modelToArray(\OCA\Passwords\Db\ModelInterface $model) {
        $data = $model->toArray();

        if(isset($data['hasShares'])) {
            $data['hasShares'] = false;
        }

        return $data;
    }
}
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

namespace OCA\Passwords\UserMigration\Import;

use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Services\EncryptionService;
use OCP\DB\Exception;

class RevisionedItemsImporter {

    public function __construct(
        protected TagMapper              $tagMapper,
        protected FolderMapper           $folderMapper,
        protected PasswordMapper         $passwordMapper,
        protected TagRevisionMapper      $tagRevisionMapper,
        protected EncryptionService      $encryptionService,
        protected FolderRevisionMapper   $folderRevisionMapper,
        protected PasswordRevisionMapper $passwordRevisionMapper,
    ) {
    }

    /**
     * @param $userId
     * @param $data
     *
     * @return void
     */
    public function importData($userId, $data): void {
        $this->importItemData($userId, $data['tags'], $this->tagMapper, $this->tagRevisionMapper, Tag::class, TagRevision::class);
        $this->importItemData($userId, $data['folders'], $this->folderMapper, $this->folderRevisionMapper, Folder::class, FolderRevision::class);
        $this->importItemData($userId, $data['passwords'], $this->passwordMapper, $this->passwordRevisionMapper, Password::class, PasswordRevision::class);
    }

    protected function importItemData(string $userId, array $items, AbstractMapper $modelMapper, AbstractMapper $revisionMapper, string $modelClass, string $revisionClass): void {
        $this->deleteItems($modelMapper, $userId);
        $this->deleteItems($revisionMapper, $userId);

        foreach($items as $item) {
            $this->createEntity($userId, $modelMapper, $item['model'], $modelClass);
            foreach($item['revisions'] as $revision) {
                $this->createEntity($userId, $revisionMapper, $revision, $revisionClass, true);
            }
        }
    }

    /**
     * @param AbstractMapper       $service
     * @param                      $userId
     *
     * @return void
     * @throws Exception
     */
    protected function deleteItems(AbstractMapper $service, $userId): void {
        $items = $service->findAllByUserId($userId);
        foreach($items as $item) {
            $service->delete($item);
        }
        $items = $service->findAllDeleted($userId);
        foreach($items as $item) {
            $service->delete($item);
        }
    }

    /**
     * @param AbstractMapper $mapper
     * @param array          $data
     * @param string         $modelClass
     *
     * @return void
     * @throws Exception
     */
    protected function createEntity(string $userId, AbstractMapper $mapper, array $data, string $modelClass, bool $encrypt = false): void {
        $model = new $modelClass();
        foreach($data as $property => $value) {
            if($property !== 'id') {
                $model->setProperty($property, $value);
            }
        }
        $model->setUserId($userId);
        if($encrypt) {
            $model->_setDecrypted(true);
            $model = $this->encryptionService->encrypt($model);
        }

        $mapper->insert($model);
    }
}
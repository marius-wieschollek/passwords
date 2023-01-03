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
use OCA\Passwords\Db\Challenge;
use OCA\Passwords\Db\ChallengeMapper;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Db\KeychainMapper;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordTagRelation;
use OCA\Passwords\Db\PasswordTagRelationMapper;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;
use OCP\DB\Exception;

class SimpleItemImporter {

    public function __construct(protected KeychainMapper $keychainMapper, protected ChallengeMapper $challengeMapper, protected PasswordTagRelationMapper $passwordTagRelationMapper) {
    }

    /**
     * @param $userId
     * @param $data
     *
     * @return void
     */
    public function importData($userId, $data): void {
        $this->importItemData($userId, $data['keychains'], $this->keychainMapper, Keychain::class);
        $this->importItemData($userId, $data['challenges'], $this->challengeMapper, Challenge::class);
        $this->importItemData($userId, $data['tagRelations'], $this->passwordTagRelationMapper, PasswordTagRelation::class);
    }

    protected function importItemData(string $userId, array $items, AbstractMapper $modelMapper, string $modelClass): void {
        $this->deleteItems($modelMapper, $userId);

        foreach($items as $item) {
            $this->createEntity($userId, $modelMapper, $item, $modelClass);
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
    protected function createEntity(string $userId, AbstractMapper $mapper, array $data, string $modelClass): void {
        $model = new $modelClass();
        foreach($data as $property => $value) {
            if($property !== 'id') {
                $model->setProperty($property, $value);
            }
        }
        $model->setUserId($userId);

        $mapper->insert($model);
    }
}
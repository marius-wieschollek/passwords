<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EnvironmentService;

/**
 * Class FolderService
 *
 * @package OCA\Passwords\Services
 */
class FolderService extends AbstractModelService {

    const BASE_FOLDER_UUID = '00000000-0000-0000-0000-000000000000';

    /**
     * @var FolderMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = Folder::class;

    /**
     * FolderService constructor.
     *
     * @param HookManager        $hookManager
     * @param FolderMapper       $mapper
     * @param UuidHelper         $uuidHelper
     * @param EnvironmentService $environment
     */
    public function __construct(HookManager $hookManager, FolderMapper $mapper, UuidHelper $uuidHelper, EnvironmentService $environment) {
        parent::__construct($mapper, $uuidHelper, $hookManager, $environment);
    }

    /**
     * @param string $uuid
     *
     * @return \OCA\Passwords\Db\EntityInterface|Folder
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid): ModelInterface {
        if($uuid === self::BASE_FOLDER_UUID) return $this->getBaseFolder();

        return parent::findByUuid($uuid);
    }

    /**
     * @param string $uuid
     *
     * @return Folder[]
     */
    public function findByParent(string $uuid): array {
        return $this->mapper->findAllByParentFolder($uuid);
    }

    /**
     * @return Folder|ModelInterface
     */
    public function getBaseFolder(): Folder {

        $model = $this->createModel();
        $model->setRevision(FolderRevisionService::BASE_REVISION_UUID);
        $model->setUuid(self::BASE_FOLDER_UUID);

        return $model;
    }

    /**
     * @param ModelInterface|EntityInterface $model
     *
     * @return Folder|\OCP\AppFramework\Db\Entity
     */
    public function save(EntityInterface $model): EntityInterface {
        if($model->getUuid() === self::BASE_FOLDER_UUID) return $model;

        return parent::save($model);
    }

    /**
     * @param ModelInterface|EntityInterface $entity
     * @param array                          $overwrites
     *
     * @return EntityInterface
     * @throws \Exception
     */
    public function clone(EntityInterface $entity, array $overwrites = []): EntityInterface {
        if($entity->getUuid() === self::BASE_FOLDER_UUID) return $entity;

        return parent::clone($entity, $overwrites);
    }

    /**
     * @param ModelInterface|EntityInterface $model
     *
     * @throws \Exception
     */
    public function delete(EntityInterface $model): void {
        if($model->getUuid() === self::BASE_FOLDER_UUID) return;

        parent::delete($model);
    }

    /**
     * @param ModelInterface    $model
     * @param RevisionInterface $revision
     *
     * @throws \Exception
     */
    public function setRevision(ModelInterface $model, RevisionInterface $revision): void {
        if($model->getUuid() === self::BASE_FOLDER_UUID) return;

        parent::setRevision($model, $revision);
    }
}
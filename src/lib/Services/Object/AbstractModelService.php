<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class AbstractModelService
 *
 * @package OCA\AbstractParentEntitys\Services\Object
 */
abstract class AbstractModelService extends AbstractService {

    /**
     * AbstractModelService constructor.
     *
     * @param AbstractMapper     $mapper
     * @param UuidHelper         $uuidHelper
     * @param HookManager        $hookManager
     * @param EnvironmentService $environment
     */
    public function __construct(AbstractMapper $mapper, UuidHelper $uuidHelper, HookManager $hookManager, EnvironmentService $environment) {
        $this->mapper = $mapper;

        parent::__construct($uuidHelper, $hookManager, $environment);
    }

    /**
     * @return ModelInterface[]
     */
    public function findAll(): array {
        return $this->mapper->findAll();
    }

    /**
     * @param string $uuid
     *
     * @return ModelInterface|EntityInterface
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid): ModelInterface {
        return $this->mapper->findByUuid($uuid);
    }

    /**
     * @param $search
     *
     * @return ModelInterface|EntityInterface|null
     * @deprecated
     * @throws MultipleObjectsReturnedException
     */
    public function findByIdOrUuid($search): ?ModelInterface {
        try {
            return $this->mapper->findOneByIdOrUuid($search);
        } catch(DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * @return ModelInterface
     */
    public function create(): ModelInterface {
        $model = $this->createModel();
        $this->hookManager->emit($this->class, 'postCreate', [$model]);

        return $model;
    }

    /**
     * @param EntityInterface|ModelInterface|Entity $model
     *
     * @return ModelInterface|Entity
     */
    public function save(EntityInterface $model): EntityInterface {
        $this->hookManager->emit($this->class, 'preSave', [$model]);
        if(empty($model->getId())) {
            $saved = $this->mapper->insert($model);
        } else {
            $model->setUpdated(time());
            $saved = $this->mapper->update($model);
        }
        $this->hookManager->emit($this->class, 'postSave', [$saved]);

        return $saved;
    }

    /**
     * @param ModelInterface    $model
     * @param RevisionInterface $revision
     *
     * @throws \Exception
     */
    public function setRevision(ModelInterface $model, RevisionInterface $revision): void {
        if($revision->getModel() === $model->getUuid()) {
            $this->hookManager->emit($this->class, 'preSetRevision', [$model, $revision]);
            $model->setRevision($revision->getUuid());
            $this->save($model);
            $this->hookManager->emit($this->class, 'postSetRevision', [$model, $revision]);
        } else {
            throw new \Exception('Revision did not belong to model when setting model revision');
        }
    }

    /**
     * @return ModelInterface
     */
    protected function createModel(): ModelInterface {
        /** @var ModelInterface $model */
        $model = new $this->class();
        $model->setDeleted(false);
        $model->setUserId($this->userId);
        $model->setUuid($this->uuidHelper->generateUuid());
        $model->setCreated(time());
        $model->setUpdated(time());

        return $model;
    }

    /**
     * @param ModelInterface|EntityInterface $original
     * @param array                          $overwrites
     *
     * @return ModelInterface
     */
    protected function cloneModel(EntityInterface $original, array $overwrites = []): EntityInterface {

        /** @var ModelInterface $clone */
        $clone = parent::cloneModel($original, $overwrites);
        $clone->setUuid($this->uuidHelper->generateUuid());

        return $clone;
    }

}
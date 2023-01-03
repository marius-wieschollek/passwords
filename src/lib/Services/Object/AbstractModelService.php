<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use Exception;
use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;

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
     * @param IEventDispatcher   $eventDispatcher
     * @param EnvironmentService $environment
     */
    public function __construct(AbstractMapper $mapper, UuidHelper $uuidHelper, IEventDispatcher $eventDispatcher, EnvironmentService $environment) {
        $this->mapper = $mapper;

        parent::__construct($uuidHelper, $eventDispatcher, $environment);
    }

    /**
     * Cunt all revisions
     *
     * @return int
     */
    public function count() {
        return $this->mapper->count();
    }

    /**
     * @return ModelInterface[]
     */
    public function findAll(?string $userId = null): array {
        if($userId === null) {
            return $this->mapper->findAll();
        } else {
            return $this->mapper->findAllByUserId($userId);
        }
    }

    /**
     * @param string $uuid
     *
     * @return ModelInterface|EntityInterface
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid): ModelInterface {
        return $this->mapper->findByUuid($uuid);
    }

    /**
     * @return ModelInterface
     */
    public function create(): ModelInterface {
        $model = $this->createModel();
        $this->fireEvent('instantiated', $model);

        return $model;
    }

    /**
     * @param ModelInterface    $model
     * @param RevisionInterface $revision
     *
     * @throws Exception
     */
    public function setRevision(ModelInterface $model, RevisionInterface $revision): void {
        if($revision->getModel() === $model->getUuid()) {
            $this->fireEvent('beforeSetRevision', $model, $revision);
            $model->setRevision($revision->getUuid());
            $this->save($model);
            $this->fireEvent('setRevision', $model, $revision);
            $this->fireEvent('afterSetRevision', $model, $revision);
        } else {
            throw new Exception('Revision did not belong to model when setting model revision');
        }
    }

    /**
     * @return ModelInterface
     */
    protected function createModel(): ModelInterface {
        /** @var ModelInterface $model */
        $model = new $this->class();
        $model->setDeleted(false);
        if($this->userId !== null) $model->setUserId($this->userId);
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
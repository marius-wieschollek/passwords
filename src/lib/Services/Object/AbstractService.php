<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EnvironmentService;

/**
 * Class AbstractService
 *
 * @package OCA\Passwords\Services\Object
 */
abstract class AbstractService {

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var HookManager
     */
    protected $hookManager;

    /**
     * @var UuidHelper
     */
    protected $uuidHelper;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var AbstractMapper
     */
    protected $mapper;

    /**
     * AbstractService constructor.
     *
     * @param UuidHelper         $uuidHelper
     * @param HookManager        $hookManager
     * @param EnvironmentService $environment
     */
    public function __construct(
        UuidHelper $uuidHelper,
        HookManager $hookManager,
        EnvironmentService $environment
    ) {
        $this->userId      = $environment->getUserId();
        $this->environment = $environment;
        $this->hookManager = $hookManager;
        $this->uuidHelper  = $uuidHelper;
    }

    /**
     * @return EntityInterface[]
     */
    public function findDeleted(): array {
        return $this->mapper->findAllDeleted();
    }

    /**
     * @param string $userId
     *
     * @return EntityInterface[]
     * @throws \Exception
     */
    public function findByUserId(string $userId): array {
        return $this->mapper->findAllByUserId($userId);
    }

    /**
     * @param string $uuid
     *
     * @return EntityInterface
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid) {
        return $this->mapper->findByUuid($uuid);
    }

    /**
     * @param EntityInterface $model
     *
     * @return mixed
     */
    abstract public function save(EntityInterface $model): EntityInterface;

    /**
     * @param EntityInterface $entity
     * @param array           $overwrites
     *
     * @return EntityInterface
     * @throws \Exception
     */
    public function clone(EntityInterface $entity, array $overwrites = []): EntityInterface {
        if(get_class($entity) !== $this->class) throw new \Exception('Invalid revision class given');
        $this->hookManager->emit($this->class, 'preClone', [$entity]);
        /** @var EntityInterface $clone */
        $clone = $this->cloneModel($entity, $overwrites);
        $this->hookManager->emit($this->class, 'postClone', [$entity, $clone]);

        return $clone;
    }

    /**
     * @param EntityInterface $entity
     *
     * @throws \Exception
     */
    public function delete(EntityInterface $entity): void {
        if(get_class($entity) !== $this->class) throw new \Exception('Invalid revision class given');
        $this->hookManager->emit($this->class, 'preDelete', [$entity]);
        $entity->setDeleted(true);
        $this->save($entity);
        $this->hookManager->emit($this->class, 'postDelete', [$entity]);
    }

    /**
     * @param EntityInterface|\OCP\AppFramework\Db\Entity $entity
     *
     * @throws \Exception
     */
    public function destroy(EntityInterface $entity): void {
        if(get_class($entity) !== $this->class) throw new \Exception('Invalid revision class given');
        $this->hookManager->emit($this->class, 'preDestroy', [$entity]);
        if(!$entity->isDeleted()) $this->delete($entity);
        $this->mapper->delete($entity);
        $this->hookManager->emit($this->class, 'postDestroy', [$entity]);
    }

    /**
     * @param EntityInterface $original
     * @param array           $overwrites
     *
     * @return EntityInterface
     */
    protected function cloneModel(EntityInterface $original, array $overwrites = []): EntityInterface {
        $class = get_class($original);
        /** @var EntityInterface $clone */
        $clone  = new $class;
        $fields = array_keys($clone->getFieldTypes());

        foreach($fields as $field) {
            if($field == 'id' || $field == 'uuid') continue;
            if(isset($overwrites[ $field ])) {
                $clone->setProperty($field, $overwrites[ $field ]);
            } else {
                $clone->setProperty($field, $original->getProperty($field));
            }
        }

        $clone->setCreated(time());
        $clone->setUpdated(time());

        return $clone;
    }
}
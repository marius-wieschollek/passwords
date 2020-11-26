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
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Class AbstractService
 *
 * @package OCA\Passwords\Services\Object
 */
abstract class AbstractService {

    /**
     * @var string|null
     */
    protected ?string $userId;

    /**
     * @var UuidHelper
     */
    protected UuidHelper $uuidHelper;

    /**
     * @var IEventDispatcher
     */
    protected IEventDispatcher $eventDispatcher;

    /**
     * @var EnvironmentService
     */
    protected EnvironmentService $environment;

    /**
     * @var string
     */
    protected string $class;

    /**
     * @var AbstractMapper
     */
    protected AbstractMapper $mapper;

    /**
     * AbstractService constructor.
     *
     * @param UuidHelper         $uuidHelper
     * @param IEventDispatcher   $eventDispatcher
     * @param EnvironmentService $environment
     */
    public function __construct(
        UuidHelper $uuidHelper,
        IEventDispatcher $eventDispatcher,
        EnvironmentService $environment
    ) {
        $this->userId          = $environment->getUserId();
        $this->environment     = $environment;
        $this->uuidHelper      = $uuidHelper;
        $this->eventDispatcher = $eventDispatcher;
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
     * @throws Exception
     */
    public function findByUserId(string $userId): array {
        return $this->mapper->findAllByUserId($userId);
    }

    /**
     * @param string $uuid
     *
     * @return EntityInterface
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
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
     * @throws Exception
     */
    public function clone(EntityInterface $entity, array $overwrites = []): EntityInterface {
        if(get_class($entity) !== $this->class) throw new Exception('Invalid revision class given');
        $clone = $this->cloneModel($entity, $overwrites);
        $this->fireEvent('cloned', $entity, $clone);
        $this->fireEvent('afterCloned', $entity, $clone);

        return $clone;
    }

    /**
     * @param EntityInterface $entity
     *
     * @throws Exception
     */
    public function delete(EntityInterface $entity): void {
        if(get_class($entity) !== $this->class) throw new Exception('Invalid revision class given');
        $this->fireEvent('beforeDeleted', $entity);
        $entity->setDeleted(true);
        $this->save($entity);
        $this->fireEvent('deleted', $entity);
        $this->fireEvent('afterDeleted', $entity);
    }

    /**
     * @param EntityInterface|Entity $entity
     *
     * @throws Exception
     */
    public function destroy(EntityInterface $entity): void {
        if(get_class($entity) !== $this->class) throw new Exception('Invalid revision class given');
        if(!$entity->isDeleted()) $this->delete($entity);
        $this->fireEvent('beforeDestroyed', $entity);
        $this->mapper->delete($entity);
        $this->fireEvent('destroyed', $entity);
        $this->fireEvent('afterDestroyed', $entity);
    }

    /**
     * @param EntityInterface $original
     * @param array           $overwrites
     *
     * @return EntityInterface
     */
    protected function cloneModel(EntityInterface $original, array $overwrites = []): EntityInterface {
        $class  = get_class($original);
        $clone  = new $class;
        $this->fireEvent('beforeCloned', $original, $clone, $overwrites);
        $fields = array_keys($clone->getFieldTypes());

        foreach($fields as $field) {
            if($field === 'id' || $field === 'uuid') continue;
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

    /**
     * @param string $name
     * @param mixed  ...$arguments
     */
    protected function fireEvent(string $name, ...$arguments) {
        $object = substr($this->class, strrpos($this->class, '\\')+1);
        $eventClassPart = ucfirst($name);
        if(substr($name, 0, 6) === 'before') {
            $eventClassPart = ucfirst(substr($name, 6));
        } else if(substr($name, 0, 5) === 'after') {
            $eventClassPart = ucfirst(substr($name, 5));
        }
        $eventClassName = "\\OCA\\Passwords\\Events\\{$object}\\{$object}{$eventClassPart}Event";

        if(class_exists($eventClassName)) {
            $eventClass = new $eventClassName(...$arguments);
            $this->eventDispatcher->dispatchTyped($eventClass);
        } else {
            \OC::$server->getLogger()->error('Missing Event: '.$eventClassName);
        }
    }
}
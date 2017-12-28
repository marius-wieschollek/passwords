<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 21:37
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractEntity;
use OCA\Passwords\Hooks\Manager\HookManager;

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
     * @var string
     */
    protected $class;

    /**
     * PasswordService constructor.
     *
     * @param string      $userId
     * @param HookManager $hookManager
     */
    public function __construct(
        ?string $userId,
        HookManager $hookManager
    ) {
        $this->userId      = $userId;
        $this->hookManager = $hookManager;
    }

    /**
     * @return string
     */
    public function generateUuidV4(): string {
        return implode('-', [
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)).bin2hex(random_bytes(1)),
            bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)).bin2hex(random_bytes(1)),
            bin2hex(random_bytes(6))
        ]);
    }

    /**
     * @param AbstractEntity $model
     *
     * @return mixed
     */
    abstract public function save(AbstractEntity $model): AbstractEntity;

    /**
     * @param AbstractEntity $entity
     * @param array          $overwrites
     *
     * @return AbstractEntity
     * @throws \Exception
     */
    public function clone(AbstractEntity $entity, array $overwrites = []): AbstractEntity {
        if(get_class($entity) !== $this->class) throw new \Exception('Invalid revision class given');
        $this->hookManager->emit($this->class, 'preClone', [$entity]);
        /** @var AbstractEntity $clone */
        $clone = $this->cloneModel($entity, $overwrites);
        $this->hookManager->emit($this->class, 'postClone', [$entity, $clone]);

        return $clone;
    }

    /**
     * @param AbstractEntity $entity
     *
     * @throws \Exception
     */
    public function delete(AbstractEntity $entity): void {
        if(get_class($entity) !== $this->class) throw new \Exception('Invalid revision class given');
        $this->hookManager->emit($this->class, 'preDelete', [$entity]);
        $entity->setDeleted(true);
        $this->save($entity);
        $this->hookManager->emit($this->class, 'postDelete', [$entity]);
    }

    /**
     * @param AbstractEntity $original
     * @param array          $overwrites
     *
     * @return AbstractEntity
     */
    protected function cloneModel(AbstractEntity $original, array $overwrites = []): AbstractEntity {
        $class = get_class($original);
        /** @var AbstractEntity $clone */
        $clone  = new $class;
        $fields = array_keys($clone->getFieldTypes());

        foreach ($fields as $field) {
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
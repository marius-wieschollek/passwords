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
use OCP\IUser;

/**
 * Class AbstractService
 *
 * @package OCA\Passwords\Services\Object
 */
abstract class AbstractService {

    /**
     * @var IUser
     */
    protected $user;

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
     * @param IUser       $user
     * @param HookManager $hookManager
     */
    public function __construct(
        IUser $user,
        HookManager $hookManager
    ) {
        $this->user        = $user;
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
     * @param AbstractEntity $object
     * @param array          $overwrites
     *
     * @return AbstractEntity
     * @throws \Exception
     */
    public function clone(AbstractEntity $object, array $overwrites = []): AbstractEntity {
        if(get_class($object) !== $this->class) throw new \Exception('Invalid revision class given');
        $this->hookManager->emit($this->class, 'preClone', [$object]);
        /** @var AbstractEntity $clone */
        $clone = $this->cloneModel($object, $overwrites);
        $this->hookManager->emit($this->class, 'postClone', [$object, $clone]);

        return $clone;
    }

    /**
     * @param AbstractEntity $revision
     *
     * @throws \Exception
     */
    public function delete(AbstractEntity $revision): void {
        if(get_class($revision) !== $this->class) throw new \Exception('Invalid revision class given');
        $this->hookManager->emit($this->class, 'preDelete', [$revision]);
        $revision->setDeleted(true);
        $this->save($revision);
        $this->hookManager->emit($this->class, 'postDelete', [$revision]);
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
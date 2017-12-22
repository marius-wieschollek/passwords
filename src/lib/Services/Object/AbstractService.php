<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 21:37
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractEncryptedEntity;
use OCA\Passwords\Db\AbstractEntity;
use OCA\Passwords\Db\AbstractParentEntity;

/**
 * Class AbstractService
 *
 * @package OCA\Passwords\Services\Object
 */
abstract class AbstractService {

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

        if(is_subclass_of($original, AbstractEncryptedEntity::class)) {
            /** @var AbstractEncryptedEntity $clone */
            /** @var AbstractEncryptedEntity $original */
            $clone->_setDecrypted($original->_isDecrypted());
            $clone->setUuid($this->generateUuidV4());
        }
        if(is_subclass_of($original, AbstractParentEntity::class)) {
            /** @var AbstractParentEntity $clone */
            /** @var AbstractParentEntity $original */
            $clone->setUuid($this->generateUuidV4());
        }

        return $clone;
    }
}
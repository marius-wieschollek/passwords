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

/**
 * Class AbstractService
 *
 * @package OCA\Passwords\Services\Object
 */
abstract class AbstractService {

    /**
     * @param AbstractEntity $original
     * @param array          $overwrites
     *
     * @return AbstractEntity
     */
    protected function cloneModel(AbstractEntity $original, array $overwrites = []) {
        $class = get_class($original);
        /** @var AbstractEntity $clone */
        $clone = new $class;
        $fields = array_keys($clone->getFieldTypes());

        foreach ($fields as $field) {
            if($field == 'id' || $field == 'uuid') continue;
            if(isset($overwrites[$field])) {
                $clone->setProperty($field, $overwrites[$field]);
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
        }

        return $clone;
    }
}
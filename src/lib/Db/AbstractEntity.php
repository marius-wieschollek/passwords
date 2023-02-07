<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use BadFunctionCallException;
use OCP\AppFramework\Db\Entity;

/**
 * Class AbstractEntity
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method bool getDeleted()
 * @method void setDeleted(bool $deleted)
 * @method int getCreated()
 * @method void setCreated(int $created)
 * @method int getUpdated()
 * @method void setUpdated(int $updated)
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractEntity extends Entity implements EntityInterface {

    /**
     * @var string
     */
    protected string $uuid;

    /**
     * @var string
     */
    protected string $userId;

    /**
     * @var bool
     */
    protected bool $deleted;

    /**
     * @var int
     */
    protected int $created;

    /**
     * @var int
     */
    protected int $updated;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('uuid', 'string');
        $this->addType('userId', 'string');
        $this->addType('deleted', 'boolean');
        $this->addType('created', 'integer');
        $this->addType('updated', 'integer');
    }

    /**
     * @param string $name
     * @param array  $args
     * @TODO Temporary fix for NC25/26 cross compatibility. Rename to "setter" in 2024.1.0
     */
    protected function _setter(string $name, array $args): void {
        if(property_exists($this, $name)) {
            if(isset($this->{$name}) && $this->{$name} === $args[0]) {
                return;
            }
            $this->markFieldUpdated($name);

            if($args[0] !== null && array_key_exists($name, $this->getFieldTypes())) {
                $type = $this->getFieldTypes()[ $name ];
                if($type === 'blob') $type = 'string';

                settype($args[0], $type);
            }
            $this->{$name} = $args[0];
        } else {
            throw new BadFunctionCallException($name.' is not a valid attribute');
        }
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @TODO Temporary fix for NC25/26 cross compatibility. Rename to "getter" in 2024.1.0
     */
    protected function _getter(string $name): mixed {
        if(property_exists($this, $name)) {
            return isset($this->{$name}) ? $this->{$name}:null;
        } else {
            throw new BadFunctionCallException($name.' is not a valid attribute');
        }
    }

    /**
     * Each time a setter is called, push the part after set
     * into an array: for instance setId will save Id in the
     * updated fields array so it can be easily used to create the
     * getter method
     * @since 7.0.0
     * @TODO Remove in 2024.1.0
     */
    public function __call(string $methodName, array $args) {
        if (strpos($methodName, 'set') === 0) {
            $this->_setter(lcfirst(substr($methodName, 3)), $args);
        } elseif (strpos($methodName, 'get') === 0) {
            return $this->_getter(lcfirst(substr($methodName, 3)));
        } elseif ($this->isGetterForBoolProperty($methodName)) {
            return $this->_getter(lcfirst(substr($methodName, 2)));
        } else {
            throw new \BadFunctionCallException($methodName .' does not exist');
        }
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool {
        return $this->getDeleted();
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function getProperty(string $property) {
        // @TODO Change to "getter" in 2024.1.0
        return $this->_getter($property);
    }

    /**
     * @param string $property
     * @param        $value
     */
    public function setProperty(string $property, $value): void {
        // @TODO Change to "setter" in 2024.1.0
        $this->_setter($property, [$value]);
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function hasProperty(string $property): bool {
        $fieldTypes = $this->getFieldTypes();

        return isset($fieldTypes[ $property ]);
    }

    /**
     * @return array
     */
    public function toArray(): array {
        $fields = array_keys($this->getFieldTypes());

        $array = [];
        foreach($fields as $field) {
            $array[ $field ] = $this->getProperty($field);
        }

        return $array;
    }
}
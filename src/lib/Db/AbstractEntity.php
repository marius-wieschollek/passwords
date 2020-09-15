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
     */
    protected function setter($name, $args) {
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
     */
    protected function getter($name) {
        if(property_exists($this, $name)) {
            return isset($this->{$name}) ? $this->{$name}:null;
        } else {
            throw new BadFunctionCallException($name.' is not a valid attribute');
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
        return $this->getter($property);
    }

    /**
     * @param string $property
     * @param        $value
     */
    public function setProperty(string $property, $value): void {
        $this->setter($property, [$value]);
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
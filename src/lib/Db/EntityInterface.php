<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Interface EntityInterface
 *
 * @method integer getId()
 * @method void setId(integer $id)
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
interface EntityInterface {

    /**
     * @return bool
     */
    public function isDeleted(): bool;

    /**
     * @return array
     */
    public function getFieldTypes();

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function getProperty(string $property);

    /**
     * @param string $property
     * @param        $value
     */
    public function setProperty(string $property, $value): void;

    /**
     * @param string $property
     *
     * @return bool
     */
    public function hasProperty(string $property): bool;

    /**
     * @return bool
     */
    public function toArray(): array;

}
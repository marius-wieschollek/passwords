<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 30.12.17
 * Time: 20:24
 */

namespace OCA\Passwords\Db;

/**
 * Interface EntityInterface
 *
 * @method integer getId()
 * @method void setId(integer $id)
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

}
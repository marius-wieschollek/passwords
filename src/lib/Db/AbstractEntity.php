<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 27.08.17
 * Time: 14:22
 */

namespace OCA\Passwords\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class AbstractEntity
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractEntity extends Entity {

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
        $method = 'set'.ucfirst($property);
        $this->{$method}($value);
    }
}
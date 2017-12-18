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
abstract class AbstractEntity extends Entity {

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var bool
     */
    protected $deleted;

    /**
     * @var int
     */
    protected $created;

    /**
     * @var int
     */
    protected $updated;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('userId', 'string');
        $this->addType('deleted', 'boolean');
        $this->addType('created', 'integer');
        $this->addType('updated', 'integer');
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
        $method = 'set'.ucfirst($property);
        $this->{$method}($value);
    }
}
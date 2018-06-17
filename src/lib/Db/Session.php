<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class Session
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getCreated()
 * @method void setCreated(int $created)
 * @method int getUpdated()
 * @method void setUpdated(int $updated)
 * @method string getData()
 * @method void setData(string $data)
 *
 * @package OCA\Passwords\Db
 */
class Session extends Entity {

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var string
     */
    protected $userId;

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
        $this->addType('uuid', 'string');
        $this->addType('data', 'string');
        $this->addType('userId', 'string');
        $this->addType('created', 'integer');
        $this->addType('updated', 'integer');
    }
}
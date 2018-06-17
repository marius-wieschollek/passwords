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
 * @method string getData()
 * @method void setData(string $data)
 *
 * @package OCA\Passwords\Db
 */
class Session extends AbstractEntity {

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $data;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('uuid', 'string');
        $this->addType('data', 'string');

        parent::__construct();
    }
}
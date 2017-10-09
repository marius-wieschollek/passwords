<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 14:11
 */

namespace OCA\Passwords\Db;

/**
 * Class Folder
 *
 * @method string getName()
 * @method void setName(string $name)
 *
 * @package OCA\Passwords\Db
 */
class Folder extends AbstractEncryptedEntity {

    /**
     * @var string
     */
    protected $name;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('name', 'string');

        parent::__construct();
    }
}
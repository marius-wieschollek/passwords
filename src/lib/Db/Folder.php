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
 * @package OCA\Passwords\Db
 * @method string getSuspended()
 * @method void setSuspended(bool $suspended)
 */
class Folder extends AbstractParentEntity {
    /**
     * @var string
     */
    protected $suspended;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('suspended', 'boolean');

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function isSuspended(): bool {
        return $this->getSuspended()===true;
    }
}
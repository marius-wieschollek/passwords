<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 20:35
 */

namespace OCA\Passwords\Db;

use JsonSerializable;

/**
 * Class Password
 *
 * @package OCA\Passwords\Db
 * @method string getSuspended()
 * @method void setSuspended(bool $suspended)
 */
class Password extends AbstractParentEntity {
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
        return $this->getSuspended();
    }
}
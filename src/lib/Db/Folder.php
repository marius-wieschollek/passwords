<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class Folder
 *
 * @method bool getSuspended()
 * @method void setSuspended(bool $suspended)
 *
 * @package OCA\Passwords\Db
 */
class Folder extends AbstractModel {

    /**
     * @var bool
     */
    protected bool $suspended;

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
        return $this->getSuspended() === true;
    }
}
<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\Password;

use OCA\Passwords\Db\Password;
use OCP\EventDispatcher\Event;

/**
 * Class GenericPasswordEvent
 *
 * @package OCA\Passwords\Events\Password
 */
class GenericPasswordEvent extends Event {

    /**
     * @var Password
     */
    protected Password $password;

    /**
     * GenericPasswordEvent constructor.
     *
     * @param Password $password
     */
    public function __construct(Password $password) {
        parent::__construct();
        $this->password = $password;
    }

    /**
     * @return Password
     */
    public function getPassword(): Password {
        return $this->password;
    }
}
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
 * Class PasswordClonedEvent
 *
 * @package OCA\Passwords\Events\Password
 */
class PasswordClonedEvent extends Event {

    /**
     * @var Password
     */
    protected Password $original;

    /**
     * @var Password
     */
    protected Password $clone;

    /**
     * PasswordClonedEvent constructor.
     *
     * @param Password $original
     * @param Password $clone
     */
    public function __construct(Password $original, Password $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
    }

    /**
     * @return Password
     */
    public function getOriginal(): Password {
        return $this->original;
    }

    /**
     * @return Password
     */
    public function getClone(): Password {
        return $this->clone;
    }
}
<?php
/*
 * @copyright 2025 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
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
     * PasswordClonedEvent constructor.
     *
     * @param Password $original
     * @param Password $clone
     */
    public function __construct(protected Password $original, protected Password $clone) {
        parent::__construct();
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
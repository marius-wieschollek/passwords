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

namespace OCA\Passwords\Events\Registration;

use OCA\Passwords\Db\Registration;
use OCP\EventDispatcher\Event;

/**
 * Class AfterRegistrationClonedEvent
 *
 * @package OCA\Passwords\Events\Registration
 */
class AfterRegistrationClonedEvent extends Event {

    /**
     * BeforeRegistrationClonedEvent constructor.
     *
     * @param Registration $original
     * @param Registration $clone
     */
    public function __construct(protected Registration $original, protected Registration $clone) {
        parent::__construct();
    }

    /**
     * @return Registration
     */
    public function getOriginal(): Registration {
        return $this->original;
    }

    /**
     * @return Registration
     */
    public function getClone(): Registration {
        return $this->clone;
    }
}
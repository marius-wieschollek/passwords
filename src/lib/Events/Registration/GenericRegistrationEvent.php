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
 * Class GenericRegistrationEvent
 *
 * @package OCA\Passwords\Events\Registration
 */
class GenericRegistrationEvent extends Event {

    /**
     * GenericRegistrationEvent constructor.
     *
     * @param Registration $Registration
     */
    public function __construct(protected Registration $Registration) {
        parent::__construct();
    }

    /**
     * @return Registration
     */
    public function getRegistration(): Registration {
        return $this->Registration;
    }
}
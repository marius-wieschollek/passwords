<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
     * @var Registration
     */
    protected Registration $Registration;

    /**
     * GenericRegistrationEvent constructor.
     *
     * @param Registration $Registration
     */
    public function __construct(Registration $Registration) {
        parent::__construct();
        $this->Registration = $Registration;
    }

    /**
     * @return Registration
     */
    public function getRegistration(): Registration {
        return $this->Registration;
    }
}
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
 * Class RegistrationClonedEvent
 *
 * @package OCA\Passwords\Events\Registration
 */
class RegistrationClonedEvent extends Event {

    /**
     * @var Registration
     */
    protected Registration $original;

    /**
     * @var Registration
     */
    protected Registration $clone;

    /**
     * RegistrationClonedEvent constructor.
     *
     * @param Registration $original
     * @param Registration $clone
     */
    public function __construct(Registration $original, Registration $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
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
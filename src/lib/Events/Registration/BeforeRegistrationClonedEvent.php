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
 * Class BeforeRegistrationClonedEvent
 *
 * @package OCA\Passwords\Events\Registration
 */
class BeforeRegistrationClonedEvent extends Event {

    /**
     * @var Registration
     */
    protected Registration $original;

    /**
     * @var Registration
     */
    protected Registration $clone;

    /**
     * @var array
     */
    protected array        $overwrites;

    /**
     * BeforeRegistrationClonedEvent constructor.
     *
     * @param Registration $original
     * @param Registration $clone
     * @param array        $overwrites
     */
    public function __construct(Registration $original, Registration $clone, array $overwrites) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
        $this->overwrites = $overwrites;
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

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
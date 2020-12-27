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
 * Class BeforePasswordClonedEvent
 *
 * @package OCA\Passwords\Events\Password
 */
class BeforePasswordClonedEvent extends Event {

    /**
     * @var Password
     */
    protected Password $original;

    /**
     * @var Password
     */
    protected Password $clone;

    /**
     * @var array
     */
    protected array    $overwrites;

    /**
     * BeforePasswordClonedEvent constructor.
     *
     * @param Password $original
     * @param Password $clone
     * @param array    $overwrites
     */
    public function __construct(Password $original, Password $clone, array $overwrites) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
        $this->overwrites = $overwrites;
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

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
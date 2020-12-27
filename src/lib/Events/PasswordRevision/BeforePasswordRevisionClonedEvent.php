<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\PasswordRevision;

use OCA\Passwords\Db\PasswordRevision;
use OCP\EventDispatcher\Event;

/**
 * Class BeforePasswordRevisionClonedEvent
 *
 * @package OCA\Passwords\Events\PasswordRevision
 */
class BeforePasswordRevisionClonedEvent extends Event {

    /**
     * @var PasswordRevision
     */
    protected PasswordRevision $original;

    /**
     * @var PasswordRevision
     */
    protected PasswordRevision $clone;

    /**
     * @var array
     */
    protected array            $overwrites;

    /**
     * BeforePasswordRevisionClonedEvent constructor.
     *
     * @param PasswordRevision $original
     * @param PasswordRevision $clone
     * @param array            $overwrites
     */
    public function __construct(PasswordRevision $original, PasswordRevision $clone, array $overwrites) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
        $this->overwrites = $overwrites;
    }

    /**
     * @return PasswordRevision
     */
    public function getOriginal(): PasswordRevision {
        return $this->original;
    }

    /**
     * @return PasswordRevision
     */
    public function getClone(): PasswordRevision {
        return $this->clone;
    }

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
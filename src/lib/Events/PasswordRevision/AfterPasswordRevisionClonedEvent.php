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
 * Class AfterPasswordRevisionClonedEvent
 *
 * @package OCA\Passwords\Events\PasswordRevision
 */
class AfterPasswordRevisionClonedEvent extends Event {

    /**
     * @var PasswordRevision
     */
    protected PasswordRevision $original;

    /**
     * @var PasswordRevision
     */
    protected PasswordRevision $clone;

    /**
     * BeforePasswordRevisionClonedEvent constructor.
     *
     * @param PasswordRevision $original
     * @param PasswordRevision $clone
     */
    public function __construct(PasswordRevision $original, PasswordRevision $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
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
}
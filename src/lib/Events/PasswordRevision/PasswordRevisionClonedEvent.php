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

namespace OCA\Passwords\Events\PasswordRevision;

use OCA\Passwords\Db\PasswordRevision;
use OCP\EventDispatcher\Event;

/**
 * Class PasswordRevisionClonedEvent
 *
 * @package OCA\Passwords\Events\PasswordRevision
 */
class PasswordRevisionClonedEvent extends Event {

    /**
     * PasswordRevisionClonedEvent constructor.
     *
     * @param PasswordRevision $original
     * @param PasswordRevision $clone
     */
    public function __construct(protected PasswordRevision $original, protected PasswordRevision $clone) {
        parent::__construct();
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
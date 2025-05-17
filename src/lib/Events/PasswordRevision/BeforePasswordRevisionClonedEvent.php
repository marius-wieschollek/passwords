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
 * Class BeforePasswordRevisionClonedEvent
 *
 * @package OCA\Passwords\Events\PasswordRevision
 */
class BeforePasswordRevisionClonedEvent extends Event {

    /**
     * BeforePasswordRevisionClonedEvent constructor.
     *
     * @param PasswordRevision $original
     * @param PasswordRevision $clone
     * @param array            $overwrites
     */
    public function __construct(protected PasswordRevision $original, protected PasswordRevision $clone, protected array $overwrites) {
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

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
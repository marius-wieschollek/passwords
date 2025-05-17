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
 * Class GenericPasswordRevisionEvent
 *
 * @package OCA\Passwords\Events\PasswordRevision
 */
class GenericPasswordRevisionEvent extends Event {

    /**
     * GenericPasswordRevisionEvent constructor.
     *
     * @param PasswordRevision $PasswordRevision
     */
    public function __construct(protected PasswordRevision $PasswordRevision) {
        parent::__construct();
    }

    /**
     * @return PasswordRevision
     */
    public function getPasswordRevision(): PasswordRevision {
        return $this->PasswordRevision;
    }
}
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
 * Class GenericPasswordRevisionEvent
 *
 * @package OCA\Passwords\Events\PasswordRevision
 */
class GenericPasswordRevisionEvent extends Event {

    /**
     * @var PasswordRevision
     */
    protected PasswordRevision $PasswordRevision;

    /**
     * GenericPasswordRevisionEvent constructor.
     *
     * @param PasswordRevision $PasswordRevision
     */
    public function __construct(PasswordRevision $PasswordRevision) {
        parent::__construct();
        $this->PasswordRevision = $PasswordRevision;
    }

    /**
     * @return PasswordRevision
     */
    public function getPasswordRevision(): PasswordRevision {
        return $this->PasswordRevision;
    }
}
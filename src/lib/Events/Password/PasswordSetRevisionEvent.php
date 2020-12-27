<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\Password;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCP\EventDispatcher\Event;

/**
 * Class PasswordSetRevisionEvent
 *
 * @package OCA\Passwords\Events\Password
 */
class PasswordSetRevisionEvent extends Event {

    /**
     * @var Password
     */
    protected Password $password;

    /**
     * @var PasswordRevision
     */
    protected PasswordRevision $revision;

    /**
     * PasswordSetRevisionEvent constructor.
     *
     * @param Password         $password
     * @param PasswordRevision $revision
     */
    public function __construct(Password $password, PasswordRevision $revision) {
        parent::__construct();
        $this->password = $password;
        $this->revision = $revision;
    }

    /**
     * @return PasswordRevision
     */
    public function getRevision(): PasswordRevision {
        return $this->revision;
    }

    /**
     * @return Password
     */
    public function getPassword(): Password {
        return $this->password;
    }
}
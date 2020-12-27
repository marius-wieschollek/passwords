<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\PasswordTagRelation;

use OCA\Passwords\Db\PasswordTagRelation;
use OCP\EventDispatcher\Event;

/**
 * Class AfterPasswordTagRelationClonedEvent
 *
 * @package OCA\Passwords\Events\PasswordTagRelation
 */
class AfterPasswordTagRelationClonedEvent extends Event {

    /**
     * @var PasswordTagRelation
     */
    protected PasswordTagRelation $original;

    /**
     * @var PasswordTagRelation
     */
    protected PasswordTagRelation $clone;

    /**
     * BeforePasswordTagRelationClonedEvent constructor.
     *
     * @param PasswordTagRelation $original
     * @param PasswordTagRelation $clone
     */
    public function __construct(PasswordTagRelation $original, PasswordTagRelation $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
    }

    /**
     * @return PasswordTagRelation
     */
    public function getOriginal(): PasswordTagRelation {
        return $this->original;
    }

    /**
     * @return PasswordTagRelation
     */
    public function getClone(): PasswordTagRelation {
        return $this->clone;
    }
}
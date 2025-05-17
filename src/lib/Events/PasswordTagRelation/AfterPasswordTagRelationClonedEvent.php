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
     * BeforePasswordTagRelationClonedEvent constructor.
     *
     * @param PasswordTagRelation $original
     * @param PasswordTagRelation $clone
     */
    public function __construct(protected PasswordTagRelation $original, protected PasswordTagRelation $clone) {
        parent::__construct();
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
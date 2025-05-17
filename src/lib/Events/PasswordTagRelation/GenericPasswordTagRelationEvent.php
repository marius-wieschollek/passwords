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
 * Class GenericPasswordTagRelationEvent
 *
 * @package OCA\Passwords\Events\PasswordTagRelation
 */
class GenericPasswordTagRelationEvent extends Event {

    /**
     * GenericPasswordTagRelationEvent constructor.
     *
     * @param PasswordTagRelation $PasswordTagRelation
     */
    public function __construct(protected PasswordTagRelation $PasswordTagRelation) {
        parent::__construct();
    }

    /**
     * @return PasswordTagRelation
     */
    public function getPasswordTagRelation(): PasswordTagRelation {
        return $this->PasswordTagRelation;
    }
}
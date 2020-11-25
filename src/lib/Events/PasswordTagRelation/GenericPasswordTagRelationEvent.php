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
 * Class GenericPasswordTagRelationEvent
 *
 * @package OCA\Passwords\Events\PasswordTagRelation
 */
class GenericPasswordTagRelationEvent extends Event {

    /**
     * @var PasswordTagRelation
     */
    protected PasswordTagRelation $PasswordTagRelation;

    /**
     * GenericPasswordTagRelationEvent constructor.
     *
     * @param PasswordTagRelation $PasswordTagRelation
     */
    public function __construct(PasswordTagRelation $PasswordTagRelation) {
        parent::__construct();
        $this->PasswordTagRelation = $PasswordTagRelation;
    }

    /**
     * @return PasswordTagRelation
     */
    public function getPasswordTagRelation(): PasswordTagRelation {
        return $this->PasswordTagRelation;
    }
}
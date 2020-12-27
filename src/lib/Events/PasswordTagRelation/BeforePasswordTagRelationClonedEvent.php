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
 * Class BeforePasswordTagRelationClonedEvent
 *
 * @package OCA\Passwords\Events\PasswordTagRelation
 */
class BeforePasswordTagRelationClonedEvent extends Event {

    /**
     * @var PasswordTagRelation
     */
    protected PasswordTagRelation $original;

    /**
     * @var PasswordTagRelation
     */
    protected PasswordTagRelation $clone;

    /**
     * @var array
     */
    protected array               $overwrites;

    /**
     * BeforePasswordTagRelationClonedEvent constructor.
     *
     * @param PasswordTagRelation $original
     * @param PasswordTagRelation $clone
     * @param array               $overwrites
     */
    public function __construct(PasswordTagRelation $original, PasswordTagRelation $clone, array $overwrites) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
        $this->overwrites = $overwrites;
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

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
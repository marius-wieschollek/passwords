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

namespace OCA\Passwords\Events\Tag;

use OCA\Passwords\Db\Tag;
use OCP\EventDispatcher\Event;

/**
 * Class AfterTagClonedEvent
 *
 * @package OCA\Passwords\Events\Tag
 */
class AfterTagClonedEvent extends Event {

    /**
     * BeforeTagClonedEvent constructor.
     *
     * @param Tag $original
     * @param Tag $clone
     */
    public function __construct(protected Tag $original, protected Tag $clone) {
        parent::__construct();
    }

    /**
     * @return Tag
     */
    public function getOriginal(): Tag {
        return $this->original;
    }

    /**
     * @return Tag
     */
    public function getClone(): Tag {
        return $this->clone;
    }
}
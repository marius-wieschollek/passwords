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
 * Class BeforeTagClonedEvent
 *
 * @package OCA\Passwords\Events\Tag
 */
class BeforeTagClonedEvent extends Event {

    /**
     * BeforeTagClonedEvent constructor.
     *
     * @param Tag   $original
     * @param Tag   $clone
     * @param array $overwrites
     */
    public function __construct(protected Tag $original, protected Tag $clone, protected array $overwrites) {
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

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
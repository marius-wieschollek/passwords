<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
     * @var Tag
     */
    protected Tag $original;

    /**
     * @var Tag
     */
    protected Tag $clone;

    /**
     * @var array
     */
    protected array $overwrites;

    /**
     * BeforeTagClonedEvent constructor.
     *
     * @param Tag   $original
     * @param Tag   $clone
     * @param array $overwrites
     */
    public function __construct(Tag $original, Tag $clone, array $overwrites) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
        $this->overwrites = $overwrites;
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
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
 * Class TagClonedEvent
 *
 * @package OCA\Passwords\Events\Tag
 */
class TagClonedEvent extends Event {

    /**
     * @var Tag
     */
    protected Tag $original;

    /**
     * @var Tag
     */
    protected Tag $clone;

    /**
     * TagClonedEvent constructor.
     *
     * @param Tag $original
     * @param Tag $clone
     */
    public function __construct(Tag $original, Tag $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
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
<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\TagRevision;

use OCA\Passwords\Db\TagRevision;
use OCP\EventDispatcher\Event;

/**
 * Class TagRevisionClonedEvent
 *
 * @package OCA\Passwords\Events\TagRevision
 */
class TagRevisionClonedEvent extends Event {

    /**
     * @var TagRevision
     */
    protected TagRevision $original;

    /**
     * @var TagRevision
     */
    protected TagRevision $clone;

    /**
     * TagRevisionClonedEvent constructor.
     *
     * @param TagRevision $original
     * @param TagRevision $clone
     */
    public function __construct(TagRevision $original, TagRevision $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
    }

    /**
     * @return TagRevision
     */
    public function getOriginal(): TagRevision {
        return $this->original;
    }

    /**
     * @return TagRevision
     */
    public function getClone(): TagRevision {
        return $this->clone;
    }
}
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
 * Class BeforeTagRevisionClonedEvent
 *
 * @package OCA\Passwords\Events\TagRevision
 */
class BeforeTagRevisionClonedEvent extends Event {

    /**
     * @var TagRevision
     */
    protected TagRevision $original;

    /**
     * @var TagRevision
     */
    protected TagRevision $clone;

    /**
     * @var array
     */
    protected array       $overwrites;

    /**
     * BeforeTagRevisionClonedEvent constructor.
     *
     * @param TagRevision $original
     * @param TagRevision $clone
     * @param array       $overwrites
     */
    public function __construct(TagRevision $original, TagRevision $clone, array $overwrites) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
        $this->overwrites = $overwrites;
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

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
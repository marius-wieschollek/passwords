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
     * TagRevisionClonedEvent constructor.
     *
     * @param TagRevision $original
     * @param TagRevision $clone
     */
    public function __construct(protected TagRevision $original, protected TagRevision $clone) {
        parent::__construct();
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
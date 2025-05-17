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
use OCA\Passwords\Db\TagRevision;
use OCP\EventDispatcher\Event;

/**
 * Class BeforeTagSetRevisionEvent
 *
 * @package OCA\Passwords\Events\Tag
 */
class BeforeTagSetRevisionEvent extends Event {

    /**
     * BeforeTagSetRevisionEvent constructor.
     *
     * @param Tag         $Tag
     * @param TagRevision $revision
     */
    public function __construct(protected Tag $Tag, protected TagRevision $revision) {
        parent::__construct();
    }

    /**
     * @return TagRevision
     */
    public function getRevision(): TagRevision {
        return $this->revision;
    }

    /**
     * @return Tag
     */
    public function getTag(): Tag {
        return $this->Tag;
    }
}
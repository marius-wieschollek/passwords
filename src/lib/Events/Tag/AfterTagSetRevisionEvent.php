<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\Tag;

use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;
use OCP\EventDispatcher\Event;

/**
 * Class AfterTagSetRevisionEvent
 *
 * @package OCA\Passwords\Events\Tag
 */
class AfterTagSetRevisionEvent extends Event {

    /**
     * @var Tag
     */
    protected Tag $Tag;

    /**
     * @var TagRevision
     */
    protected TagRevision $revision;

    /**
     * AfterTagSetRevisionEvent constructor.
     *
     * @param Tag         $Tag
     * @param TagRevision $revision
     */
    public function __construct(Tag $Tag, TagRevision $revision) {
        parent::__construct();
        $this->Tag = $Tag;
        $this->revision = $revision;
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
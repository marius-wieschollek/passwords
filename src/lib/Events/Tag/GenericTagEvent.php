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
 * Class GenericTagEvent
 *
 * @package OCA\Passwords\Events\Tag
 */
class GenericTagEvent extends Event {

    /**
     * @var Tag
     */
    protected Tag $Tag;

    /**
     * GenericTagEvent constructor.
     *
     * @param Tag $Tag
     */
    public function __construct(Tag $Tag) {
        parent::__construct();
        $this->Tag = $Tag;
    }

    /**
     * @return Tag
     */
    public function getTag(): Tag {
        return $this->Tag;
    }
}
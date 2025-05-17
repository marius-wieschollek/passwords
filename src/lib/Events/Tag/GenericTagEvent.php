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
 * Class GenericTagEvent
 *
 * @package OCA\Passwords\Events\Tag
 */
class GenericTagEvent extends Event {

    /**
     * GenericTagEvent constructor.
     *
     * @param Tag $Tag
     */
    public function __construct(protected Tag $Tag) {
        parent::__construct();
    }

    /**
     * @return Tag
     */
    public function getTag(): Tag {
        return $this->Tag;
    }
}
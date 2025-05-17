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

namespace OCA\Passwords\Events\Share;

use OCA\Passwords\Db\Share;
use OCP\EventDispatcher\Event;

/**
 * Class ShareClonedEvent
 *
 * @package OCA\Passwords\Events\Share
 */
class ShareClonedEvent extends Event {

    /**
     * ShareClonedEvent constructor.
     *
     * @param Share $original
     * @param Share $clone
     */
    public function __construct(protected Share $original, protected Share $clone) {
        parent::__construct();
    }

    /**
     * @return Share
     */
    public function getOriginal(): Share {
        return $this->original;
    }

    /**
     * @return Share
     */
    public function getClone(): Share {
        return $this->clone;
    }
}
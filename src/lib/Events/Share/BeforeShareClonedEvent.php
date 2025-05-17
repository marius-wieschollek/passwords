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
 * Class BeforeShareClonedEvent
 *
 * @package OCA\Passwords\Events\Share
 */
class BeforeShareClonedEvent extends Event {

    /**
     * BeforeShareClonedEvent constructor.
     *
     * @param Share $original
     * @param Share $clone
     * @param array $overwrites
     */
    public function __construct(protected Share $original, protected Share $clone, protected array $overwrites) {
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

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
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

namespace OCA\Passwords\Events\Challenge;

use OCA\Passwords\Db\Challenge;
use OCP\EventDispatcher\Event;

/**
 * Class BeforeChallengeClonedEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class BeforeChallengeClonedEvent extends Event {

    /**
     * BeforeChallengeClonedEvent constructor.
     *
     * @param Challenge $original
     * @param Challenge $clone
     * @param array     $overwrites
     */
    public function __construct(protected Challenge $original, protected Challenge $clone, protected array $overwrites) {
        parent::__construct();
    }

    /**
     * @return Challenge
     */
    public function getOriginal(): Challenge {
        return $this->original;
    }

    /**
     * @return Challenge
     */
    public function getClone(): Challenge {
        return $this->clone;
    }

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
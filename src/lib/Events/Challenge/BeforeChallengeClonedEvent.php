<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
     * @var Challenge
     */
    protected Challenge $original;

    /**
     * @var Challenge
     */
    protected Challenge $clone;

    /**
     * @var array
     */
    protected array     $overwrites;

    /**
     * BeforeChallengeClonedEvent constructor.
     *
     * @param Challenge $original
     * @param Challenge $clone
     * @param array     $overwrites
     */
    public function __construct(Challenge $original, Challenge $clone, array $overwrites) {
        parent::__construct();
        $this->original   = $original;
        $this->clone      = $clone;
        $this->overwrites = $overwrites;
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
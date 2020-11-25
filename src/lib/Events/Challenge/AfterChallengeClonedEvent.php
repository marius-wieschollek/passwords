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
 * Class AfterChallengeClonedEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class AfterChallengeClonedEvent extends Event {

    /**
     * @var Challenge
     */
    protected Challenge $original;

    /**
     * @var Challenge
     */
    protected Challenge $clone;

    /**
     * BeforeChallengeClonedEvent constructor.
     *
     * @param Challenge $original
     * @param Challenge $clone
     */
    public function __construct(Challenge $original, Challenge $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
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
}
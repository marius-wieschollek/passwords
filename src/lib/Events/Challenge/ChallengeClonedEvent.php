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
 * Class ChallengeClonedEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class ChallengeClonedEvent extends Event {

    /**
     * @var Challenge
     */
    protected Challenge $original;

    /**
     * @var Challenge
     */
    protected Challenge $clone;

    /**
     * ChallengeClonedEvent constructor.
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
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
 * Class GenericChallengeEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class GenericChallengeEvent extends Event {

    /**
     * @var Challenge
     */
    protected Challenge $Challenge;

    /**
     * GenericChallengeEvent constructor.
     *
     * @param Challenge $Challenge
     */
    public function __construct(Challenge $Challenge) {
        parent::__construct();
        $this->Challenge = $Challenge;
    }

    /**
     * @return Challenge
     */
    public function getChallenge(): Challenge {
        return $this->Challenge;
    }
}
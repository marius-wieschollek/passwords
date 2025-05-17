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
 * Class GenericChallengeEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class GenericChallengeEvent extends Event {

    /**
     * GenericChallengeEvent constructor.
     *
     * @param Challenge $Challenge
     */
    public function __construct(protected Challenge $Challenge) {
        parent::__construct();
    }

    /**
     * @return Challenge
     */
    public function getChallenge(): Challenge {
        return $this->Challenge;
    }
}
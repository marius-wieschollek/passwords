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
 * Class ChallengeActivatedEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class ChallengeActivatedEvent extends Event {

    /**
     * ChallengeActivatedEvent constructor.
     *
     * @param Challenge $challenge
     * @param string    $key
     */
    public function __construct(protected Challenge $challenge, protected string $key) {
        parent::__construct();
    }

    /**
     * @return Challenge
     */
    public function getChallenge(): Challenge {
        return $this->challenge;
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

}
<?php
/*
 * @copyright 2020 Passwords App
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
 * Class AfterChallengeActivatedEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class AfterChallengeActivatedEvent extends Event {

    /**
     * @var Challenge
     */
    protected Challenge $challenge;

    /**
     * @var string
     */
    protected string    $key;

    /**
     * ChallengeActivatedEvent constructor.
     *
     * @param Challenge $challenge
     * @param string    $key
     */
    public function __construct(Challenge $challenge, string $key) {
        parent::__construct();
        $this->challenge = $challenge;
        $this->key       = $key;
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
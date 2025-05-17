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

use OCP\EventDispatcher\Event;

/**
 * Class AfterChallengeRevertedEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class AfterChallengeRevertedEvent extends Event {

    /**
     * BeforeChallengeRevertedEvent constructor.
     *
     * @param array $previousChallenge
     */
    public function __construct(protected array $previousChallenge) {
        parent::__construct();
    }

    /**
     * The data of the challenge that will be reverted to
     *
     * @return array
     */
    public function getPreviousChallenge(): array {
        return $this->previousChallenge;
    }
}
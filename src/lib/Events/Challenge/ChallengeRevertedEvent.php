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

use OCP\EventDispatcher\Event;

/**
 * Class ChallengeRevertedEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class ChallengeRevertedEvent extends Event {

    /**
     * @var array
     */
    protected array $previousChallenge;

    /**
     * BeforeChallengeRevertedEvent constructor.
     *
     * @param array $previousChallenge
     */
    public function __construct(array $previousChallenge) {
        parent::__construct();
        $this->previousChallenge = $previousChallenge;
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
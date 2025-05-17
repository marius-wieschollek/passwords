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
 * Class BeforeChallengeActivatedEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class BeforeChallengeActivatedEvent extends Event {

    /**
     * BeforeChallengeActivatedEvent constructor.
     *
     * @param array  $clientData
     * @param string $secret
     */
    public function __construct(protected array $clientData, protected string $secret) {
        parent::__construct();
    }

    /**
     * @return array
     */
    public function getClientData(): array {
        return $this->clientData;
    }

    /**
     * @return string
     */
    public function getSecret(): string {
        return $this->secret;
    }
}
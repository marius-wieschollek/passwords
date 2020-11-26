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
 * Class BeforeChallengeActivatedEvent
 *
 * @package OCA\Passwords\Events\Challenge
 */
class BeforeChallengeActivatedEvent extends Event {

    /**
     * @var array
     */
    protected array $clientData;

    /**
     * @var string
     */
    protected string $secret;

    /**
     * BeforeChallengeActivatedEvent constructor.
     *
     * @param array  $clientData
     * @param string $secret
     */
    public function __construct(array $clientData, string $secret) {
        parent::__construct();
        $this->clientData = $clientData;
        $this->secret     = $secret;
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
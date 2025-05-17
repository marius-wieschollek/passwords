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

namespace OCA\Passwords\Events\Keychain;

use OCA\Passwords\Db\Keychain;
use OCP\EventDispatcher\Event;

/**
 * Class BeforeKeychainClonedEvent
 *
 * @package OCA\Passwords\Events\Keychain
 */
class BeforeKeychainClonedEvent extends Event {

    /**
     * BeforeKeychainClonedEvent constructor.
     *
     * @param Keychain $original
     * @param Keychain $clone
     * @param array    $overwrites
     */
    public function __construct(protected Keychain $original, protected Keychain $clone, protected array $overwrites) {
        parent::__construct();
    }

    /**
     * @return Keychain
     */
    public function getOriginal(): Keychain {
        return $this->original;
    }

    /**
     * @return Keychain
     */
    public function getClone(): Keychain {
        return $this->clone;
    }

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
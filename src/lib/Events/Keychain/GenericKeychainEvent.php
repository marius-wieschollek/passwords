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
 * Class GenericKeychainEvent
 *
 * @package OCA\Passwords\Events\Keychain
 */
class GenericKeychainEvent extends Event {

    /**
     * GenericKeychainEvent constructor.
     *
     * @param Keychain $Keychain
     */
    public function __construct(protected Keychain $Keychain) {
        parent::__construct();
    }

    /**
     * @return Keychain
     */
    public function getKeychain(): Keychain {
        return $this->Keychain;
    }
}
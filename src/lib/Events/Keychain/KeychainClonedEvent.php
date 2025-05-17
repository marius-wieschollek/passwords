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
 * Class KeychainClonedEvent
 *
 * @package OCA\Passwords\Events\Keychain
 */
class KeychainClonedEvent extends Event {

    /**
     * KeychainClonedEvent constructor.
     *
     * @param Keychain $original
     * @param Keychain $clone
     */
    public function __construct(protected Keychain $original, protected Keychain $clone) {
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
}
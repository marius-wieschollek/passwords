<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
     * @var Keychain
     */
    protected Keychain $Keychain;

    /**
     * GenericKeychainEvent constructor.
     *
     * @param Keychain $Keychain
     */
    public function __construct(Keychain $Keychain) {
        parent::__construct();
        $this->Keychain = $Keychain;
    }

    /**
     * @return Keychain
     */
    public function getKeychain(): Keychain {
        return $this->Keychain;
    }
}
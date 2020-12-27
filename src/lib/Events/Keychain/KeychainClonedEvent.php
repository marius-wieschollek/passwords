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
 * Class KeychainClonedEvent
 *
 * @package OCA\Passwords\Events\Keychain
 */
class KeychainClonedEvent extends Event {

    /**
     * @var Keychain
     */
    protected Keychain $original;

    /**
     * @var Keychain
     */
    protected Keychain $clone;

    /**
     * KeychainClonedEvent constructor.
     *
     * @param Keychain $original
     * @param Keychain $clone
     */
    public function __construct(Keychain $original, Keychain $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
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
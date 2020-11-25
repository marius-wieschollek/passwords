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
 * Class BeforeKeychainClonedEvent
 *
 * @package OCA\Passwords\Events\Keychain
 */
class BeforeKeychainClonedEvent extends Event {

    /**
     * @var Keychain
     */
    protected Keychain $original;

    /**
     * @var Keychain
     */
    protected Keychain $clone;

    /**
     * @var array
     */
    protected array    $overwrites;

    /**
     * BeforeKeychainClonedEvent constructor.
     *
     * @param Keychain $original
     * @param Keychain $clone
     * @param array    $overwrites
     */
    public function __construct(Keychain $original, Keychain $clone, array $overwrites) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
        $this->overwrites = $overwrites;
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
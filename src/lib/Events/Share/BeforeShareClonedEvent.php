<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\Share;

use OCA\Passwords\Db\Share;
use OCP\EventDispatcher\Event;

/**
 * Class BeforeShareClonedEvent
 *
 * @package OCA\Passwords\Events\Share
 */
class BeforeShareClonedEvent extends Event {

    /**
     * @var Share
     */
    protected Share $original;

    /**
     * @var Share
     */
    protected Share $clone;

    /**
     * @var array
     */
    protected array $overwrites;

    /**
     * BeforeShareClonedEvent constructor.
     *
     * @param Share $original
     * @param Share $clone
     * @param array $overwrites
     */
    public function __construct(Share $original, Share $clone, array $overwrites) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
        $this->overwrites = $overwrites;
    }

    /**
     * @return Share
     */
    public function getOriginal(): Share {
        return $this->original;
    }

    /**
     * @return Share
     */
    public function getClone(): Share {
        return $this->clone;
    }

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
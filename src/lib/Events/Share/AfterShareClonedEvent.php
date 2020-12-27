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
 * Class AfterShareClonedEvent
 *
 * @package OCA\Passwords\Events\Share
 */
class AfterShareClonedEvent extends Event {

    /**
     * @var Share
     */
    protected Share $original;

    /**
     * @var Share
     */
    protected Share $clone;

    /**
     * BeforeShareClonedEvent constructor.
     *
     * @param Share $original
     * @param Share $clone
     */
    public function __construct(Share $original, Share $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
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
}
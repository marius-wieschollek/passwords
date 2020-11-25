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
 * Class GenericShareEvent
 *
 * @package OCA\Passwords\Events\Share
 */
class GenericShareEvent extends Event {

    /**
     * @var Share
     */
    protected Share $Share;

    /**
     * GenericShareEvent constructor.
     *
     * @param Share $Share
     */
    public function __construct(Share $Share) {
        parent::__construct();
        $this->Share = $Share;
    }

    /**
     * @return Share
     */
    public function getShare(): Share {
        return $this->Share;
    }
}
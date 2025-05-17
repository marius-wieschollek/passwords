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
     * GenericShareEvent constructor.
     *
     * @param Share $Share
     */
    public function __construct(protected Share $Share) {
        parent::__construct();
    }

    /**
     * @return Share
     */
    public function getShare(): Share {
        return $this->Share;
    }
}
<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\TagRevision;

use OCA\Passwords\Db\TagRevision;
use OCP\EventDispatcher\Event;

/**
 * Class GenericTagRevisionEvent
 *
 * @package OCA\Passwords\Events\TagRevision
 */
class GenericTagRevisionEvent extends Event {

    /**
     * @var TagRevision
     */
    protected TagRevision $TagRevision;

    /**
     * GenericTagRevisionEvent constructor.
     *
     * @param TagRevision $TagRevision
     */
    public function __construct(TagRevision $TagRevision) {
        parent::__construct();
        $this->TagRevision = $TagRevision;
    }

    /**
     * @return TagRevision
     */
    public function getTagRevision(): TagRevision {
        return $this->TagRevision;
    }
}
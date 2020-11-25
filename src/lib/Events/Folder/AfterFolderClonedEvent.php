<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\Folder;

use OCA\Passwords\Db\Folder;
use OCP\EventDispatcher\Event;

/**
 * Class AfterFolderClonedEvent
 *
 * @package OCA\Passwords\Events\Folder
 */
class AfterFolderClonedEvent extends Event {

    /**
     * @var Folder
     */
    protected Folder $original;

    /**
     * @var Folder
     */
    protected Folder $clone;

    /**
     * BeforeFolderClonedEvent constructor.
     *
     * @param Folder $original
     * @param Folder $clone
     */
    public function __construct(Folder $original, Folder $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
    }

    /**
     * @return Folder
     */
    public function getOriginal(): Folder {
        return $this->original;
    }

    /**
     * @return Folder
     */
    public function getClone(): Folder {
        return $this->clone;
    }
}
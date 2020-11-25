<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\FolderRevision;

use OCA\Passwords\Db\FolderRevision;
use OCP\EventDispatcher\Event;

/**
 * Class AfterFolderRevisionClonedEvent
 *
 * @package OCA\Passwords\Events\FolderRevision
 */
class AfterFolderRevisionClonedEvent extends Event {

    /**
     * @var FolderRevision
     */
    protected FolderRevision $original;

    /**
     * @var FolderRevision
     */
    protected FolderRevision $clone;

    /**
     * BeforeFolderRevisionClonedEvent constructor.
     *
     * @param FolderRevision $original
     * @param FolderRevision $clone
     */
    public function __construct(FolderRevision $original, FolderRevision $clone) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
    }

    /**
     * @return FolderRevision
     */
    public function getOriginal(): FolderRevision {
        return $this->original;
    }

    /**
     * @return FolderRevision
     */
    public function getClone(): FolderRevision {
        return $this->clone;
    }
}
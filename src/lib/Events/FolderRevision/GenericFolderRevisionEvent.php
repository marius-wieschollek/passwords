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
 * Class GenericFolderRevisionEvent
 *
 * @package OCA\Passwords\Events\FolderRevision
 */
class GenericFolderRevisionEvent extends Event {

    /**
     * @var FolderRevision
     */
    protected FolderRevision $FolderRevision;

    /**
     * GenericFolderRevisionEvent constructor.
     *
     * @param FolderRevision $FolderRevision
     */
    public function __construct(FolderRevision $FolderRevision) {
        parent::__construct();
        $this->FolderRevision = $FolderRevision;
    }

    /**
     * @return FolderRevision
     */
    public function getFolderRevision(): FolderRevision {
        return $this->FolderRevision;
    }
}
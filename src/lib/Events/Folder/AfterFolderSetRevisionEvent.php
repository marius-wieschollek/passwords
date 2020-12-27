<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Events\Folder;

use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderRevision;
use OCP\EventDispatcher\Event;

/**
 * Class AfterFolderSetRevisionEvent
 *
 * @package OCA\Passwords\Events\Folder
 */
class AfterFolderSetRevisionEvent extends Event {

    /**
     * @var Folder
     */
    protected Folder $Folder;

    /**
     * @var FolderRevision
     */
    protected FolderRevision $revision;

    /**
     * AfterFolderSetRevisionEvent constructor.
     *
     * @param Folder         $Folder
     * @param FolderRevision $revision
     */
    public function __construct(Folder $Folder, FolderRevision $revision) {
        parent::__construct();
        $this->Folder = $Folder;
        $this->revision = $revision;
    }

    /**
     * @return FolderRevision
     */
    public function getRevision(): FolderRevision {
        return $this->revision;
    }

    /**
     * @return Folder
     */
    public function getFolder(): Folder {
        return $this->Folder;
    }
}
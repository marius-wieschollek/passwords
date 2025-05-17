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
     * AfterFolderSetRevisionEvent constructor.
     *
     * @param Folder         $Folder
     * @param FolderRevision $revision
     */
    public function __construct(protected Folder $Folder, protected FolderRevision $revision) {
        parent::__construct();
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
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
     * GenericFolderRevisionEvent constructor.
     *
     * @param FolderRevision $FolderRevision
     */
    public function __construct(protected FolderRevision $FolderRevision) {
        parent::__construct();
    }

    /**
     * @return FolderRevision
     */
    public function getFolderRevision(): FolderRevision {
        return $this->FolderRevision;
    }
}
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
 * Class FolderRevisionClonedEvent
 *
 * @package OCA\Passwords\Events\FolderRevision
 */
class FolderRevisionClonedEvent extends Event {

    /**
     * FolderRevisionClonedEvent constructor.
     *
     * @param FolderRevision $original
     * @param FolderRevision $clone
     */
    public function __construct(protected FolderRevision $original, protected FolderRevision $clone) {
        parent::__construct();
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
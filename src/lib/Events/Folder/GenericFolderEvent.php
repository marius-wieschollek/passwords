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
use OCP\EventDispatcher\Event;

/**
 * Class GenericFolderEvent
 *
 * @package OCA\Passwords\Events\Folder
 */
class GenericFolderEvent extends Event {

    /**
     * GenericFolderEvent constructor.
     *
     * @param Folder $Folder
     */
    public function __construct(protected Folder $Folder) {
        parent::__construct();
    }

    /**
     * @return Folder
     */
    public function getFolder(): Folder {
        return $this->Folder;
    }
}
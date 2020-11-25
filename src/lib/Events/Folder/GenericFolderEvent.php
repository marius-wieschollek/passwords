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
 * Class GenericFolderEvent
 *
 * @package OCA\Passwords\Events\Folder
 */
class GenericFolderEvent extends Event {

    /**
     * @var Folder
     */
    protected Folder $Folder;

    /**
     * GenericFolderEvent constructor.
     *
     * @param Folder $Folder
     */
    public function __construct(Folder $Folder) {
        parent::__construct();
        $this->Folder = $Folder;
    }

    /**
     * @return Folder
     */
    public function getFolder(): Folder {
        return $this->Folder;
    }
}
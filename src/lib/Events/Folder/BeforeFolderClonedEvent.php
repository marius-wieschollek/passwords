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
 * Class BeforeFolderClonedEvent
 *
 * @package OCA\Passwords\Events\Folder
 */
class BeforeFolderClonedEvent extends Event {

    /**
     * BeforeFolderClonedEvent constructor.
     *
     * @param Folder $original
     * @param Folder $clone
     * @param array  $overwrites
     */
    public function __construct(protected Folder $original, protected Folder $clone, protected array $overwrites) {
        parent::__construct();
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

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
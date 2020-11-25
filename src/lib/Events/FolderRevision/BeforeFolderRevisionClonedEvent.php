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
 * Class BeforeFolderRevisionClonedEvent
 *
 * @package OCA\Passwords\Events\FolderRevision
 */
class BeforeFolderRevisionClonedEvent extends Event {

    /**
     * @var FolderRevision
     */
    protected FolderRevision $original;

    /**
     * @var FolderRevision
     */
    protected FolderRevision $clone;

    /**
     * @var array
     */
    protected array          $overwrites;

    /**
     * BeforeFolderRevisionClonedEvent constructor.
     *
     * @param FolderRevision $original
     * @param FolderRevision $clone
     * @param array          $overwrites
     */
    public function __construct(FolderRevision $original, FolderRevision $clone, array $overwrites) {
        parent::__construct();
        $this->original   = $original;
        $this->clone      = $clone;
        $this->overwrites = $overwrites;
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

    /**
     * @return array
     */
    public function getOverwrites(): array {
        return $this->overwrites;
    }
}
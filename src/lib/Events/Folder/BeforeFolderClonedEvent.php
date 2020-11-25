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
 * Class BeforeFolderClonedEvent
 *
 * @package OCA\Passwords\Events\Folder
 */
class BeforeFolderClonedEvent extends Event {

    /**
     * @var Folder
     */
    protected Folder $original;

    /**
     * @var Folder
     */
    protected Folder $clone;

    /**
     * @var array
     */
    protected array  $overwrites;

    /**
     * BeforeFolderClonedEvent constructor.
     *
     * @param Folder $original
     * @param Folder $clone
     * @param array  $overwrites
     */
    public function __construct(Folder $original, Folder $clone, array $overwrites) {
        parent::__construct();
        $this->original = $original;
        $this->clone = $clone;
        $this->overwrites = $overwrites;
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
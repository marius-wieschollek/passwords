<?php
/*
 * @copyright 2020 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\EventListener\Folder;

use Exception;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Events\Folder\FolderClonedEvent;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class FolderClonedListener
 *
 * @package OCA\Passwords\EventListener\Folder
 */
class FolderClonedListener implements IEventListener {

    /**
     * @var FolderRevisionService
     */
    protected FolderRevisionService $revisionService;

    /**
     * FolderDeletedListener constructor.
     *
     * @param FolderRevisionService $revisionService
     */
    public function __construct(FolderRevisionService $revisionService) {
        $this->revisionService = $revisionService;
    }

    /**
     * @param Event $event
     *
     * @throws Exception
     */
    public function handle(Event $event): void {
        if(!($event instanceof FolderClonedEvent)) return;
        $originalFolder = $event->getOriginal();
        $clonedFolder = $event->getClone();

        /** @var FolderRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($originalFolder->getUuid());

        foreach($revisions as $revision) {
            /** @var FolderRevision $clone */
            $clone = $this->revisionService->clone($revision, ['folder' => $clonedFolder->getUuid()]);
            $this->revisionService->save($clone);
            if($revision->getUuid() === $originalFolder->getRevision()) {
                $clonedFolder->setRevision($clone->getUuid());
            }
        }
    }
}
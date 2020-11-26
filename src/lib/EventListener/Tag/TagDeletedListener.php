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

namespace OCA\Passwords\EventListener\Tag;

use Exception;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Events\Tag\TagDeletedEvent;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class TagDeletedListener
 *
 * @package OCA\Passwords\EventListener\Tag
 */
class TagDeletedListener implements IEventListener {

    /**
     * @var TagRevisionService
     */
    protected TagRevisionService $revisionService;

    /**
     * TagDeletedListener constructor.
     *
     * @param TagRevisionService $revisionService
     */
    public function __construct(TagRevisionService $revisionService) {
        $this->revisionService = $revisionService;
    }

    /**
     * @param Event $event
     *
     * @throws Exception
     */
    public function handle(Event $event): void {
        if(!($event instanceof TagDeletedEvent)) return;
        /** @var TagRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($event->getTag()->getUuid());

        foreach($revisions as $revision) {
            $this->revisionService->delete($revision);
        }
    }
}
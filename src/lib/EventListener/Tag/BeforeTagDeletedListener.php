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
use OCA\Passwords\Events\Tag\BeforeTagDeletedEvent;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class BeforeTagDeletedListener
 *
 * @package OCA\Passwords\EventListener\Tag
 */
class BeforeTagDeletedListener implements IEventListener {

    /**
     * @var PasswordTagRelationService
     */
    protected PasswordTagRelationService $relationService;

    /**
     * BeforeTagDeletedListener constructor.
     *
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(PasswordTagRelationService $relationService) {
        $this->relationService = $relationService;
    }

    /**
     * @param Event $event
     *
     * @throws Exception
     */
    public function handle(Event $event): void {
        if(!($event instanceof BeforeTagDeletedEvent)) return;
        $relations = $this->relationService->findByTag($event->getTag()->getUuid());

        foreach($relations as $relation) {
            $this->relationService->delete($relation);
        }
    }
}
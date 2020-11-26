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

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Events\Tag\BeforeTagSetRevisionEvent;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class BeforeTagSetRevisionListener
 *
 * @package OCA\Passwords\EventListener\Tag
 */
class BeforeTagSetRevisionListener implements IEventListener {

    /**
     * @var TagRevisionService
     */
    protected TagRevisionService $revisionService;

    /**
     * @var PasswordTagRelationService
     */
    protected PasswordTagRelationService $relationService;

    /**
     * @var PasswordRevisionService
     */
    protected PasswordRevisionService $passwordRevisionService;

    /**
     * PasswordHook constructor.
     *
     * @param TagRevisionService         $revisionService
     * @param PasswordRevisionService    $passwordRevisionService
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(
        TagRevisionService $revisionService,
        PasswordRevisionService $passwordRevisionService,
        PasswordTagRelationService $relationService
    ) {
        $this->revisionService         = $revisionService;
        $this->relationService         = $relationService;
        $this->passwordRevisionService = $passwordRevisionService;
    }

    /**
     * @param Event $event
     *
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function handle(Event $event): void {
        if(!($event instanceof BeforeTagSetRevisionEvent)) return;
        $tag      = $event->getTag();
        $revision = $event->getRevision();

        if($tag->getRevision() === null) return;
        /** @var TagRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($tag->getRevision());

        if($oldRevision->getHidden() !== $revision->getHidden()) {
            $relations = $this->relationService->findByTag($tag->getUuid());

            foreach($relations as $relation) {
                /** @var PasswordRevision $passwordRevision */
                $passwordRevision = $this->passwordRevisionService->findByModel($relation->getPassword());
                $relation->setHidden($revision->isHidden() || $passwordRevision->isHidden());
                $this->relationService->save($relation);
            }
        }
    }
}
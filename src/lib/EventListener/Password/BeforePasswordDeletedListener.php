<?php

namespace OCA\Passwords\EventListener\Password;

use Exception;
use OCA\Passwords\Events\Password\BeforePasswordDeletedEvent;
use OCA\Passwords\Events\Password\BeforePasswordSetRevisionEvent;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class BeforePasswordDeletedListener
 *
 * @package OCA\Passwords\EventListener\Password
 */
class BeforePasswordDeletedListener implements IEventListener {

    /**
     * @var ShareService
     */
    protected ShareService $shareService;

    /**
     * @var PasswordTagRelationService
     */
    protected PasswordTagRelationService $relationService;

    /**
     * BeforePasswordDeletedListener constructor.
     *
     * @param ShareService               $shareService
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(ShareService $shareService, PasswordTagRelationService $relationService) {
        $this->shareService = $shareService;
        $this->relationService = $relationService;
    }

    /**
     * @param Event $event
     *
     * @throws Exception
     */
    public function handle(Event $event): void {
        if(!($event instanceof BeforePasswordDeletedEvent)) return;
        $password = $event->getPassword();
        $relations = $this->relationService->findByPassword($password->getUuid());

        foreach($relations as $relation) {
            $this->relationService->delete($relation);
        }

        if($password->hasShares()) {
            $shares = $this->shareService->findBySourcePassword($password->getUuid());
            foreach($shares as $share) {
                $this->shareService->delete($share);
            }
            $password->setHasShares(false);
        }
    }
}
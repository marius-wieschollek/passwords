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

namespace OCA\Passwords\EventListener\Password;

use Exception;
use OCA\Passwords\Events\Password\BeforePasswordDeletedEvent;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\EventDispatcher\Event;

/**
 * Class BeforeTagDeletedListener
 *
 * @package OCA\Passwords\EventListener\Password
 */
class BeforePasswordDeletedListener {

    /**
     * @var ShareService
     */
    protected ShareService $shareService;

    /**
     * @var PasswordTagRelationService
     */
    protected PasswordTagRelationService $relationService;

    /**
     * BeforeTagDeletedListener constructor.
     *
     * @param ShareService               $shareService
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(ShareService $shareService, PasswordTagRelationService $relationService) {
        $this->shareService    = $shareService;
        $this->relationService = $relationService;
    }

    /**
     * @param Event $event
     *
     * @throws Exception
     */
    public function handle(Event $event): void {
        if(!($event instanceof BeforePasswordDeletedEvent)) return;
        $password  = $event->getPassword();
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
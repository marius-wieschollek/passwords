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

namespace OCA\Passwords\EventListener\User;

use InvalidArgumentException;
use OCA\Passwords\Services\BackgroundJobService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\BeforeUserCreatedEvent;
use OCP\User\Events\CreateUserEvent;

/**
 * Class BeforeUserCreatedListener
 *
 * @package OCA\Passwords\EventListener\User
 */
class BeforeUserCreatedListener implements IEventListener {

    /**
     * @var BackgroundJobService
     */
    protected BackgroundJobService $backgroundJobService;

    /**
     * UserHook constructor.
     *
     * @param BackgroundJobService $backgroundJobService
     */
    public function __construct(BackgroundJobService $backgroundJobService) {
        $this->backgroundJobService = $backgroundJobService;
    }

    /**
     * @param Event $event
     */
    public function handle(Event $event): void {
        if($event instanceof BeforeUserCreatedEvent || $event instanceof CreateUserEvent) {
            $this->preventCreationIfDeleted($event->getUid());
        }
    }

    /**
     * @param string $userId
     */
    protected function preventCreationIfDeleted(string $userId): void {
        if($this->backgroundJobService->hasDeleteUserJob($userId)) {
            throw new InvalidArgumentException("The username {$userId} is queued for deletion");
        }
    }
}
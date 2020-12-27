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

use OCA\Passwords\Services\BackgroundJobService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * Class UserDeletedListener
 *
 * @package OCA\Passwords\EventListener\User
 */
class UserDeletedListener implements IEventListener {

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
        if($event instanceof UserDeletedEvent) {
            $this->backgroundJobService->addDeleteUserJob($event->getUser()->getUID());
        }
    }
}
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

namespace OCA\Passwords\EventListener\Share;

use Exception;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Events\Share\ShareDeletedEvent;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class ShareDeletedListener
 *
 * @package OCA\Passwords\EventListener\Share
 */
class ShareDeletedListener implements IEventListener {

    /**
     * @var PasswordService
     */
    protected PasswordService $passwordService;

    /**
     * @var ShareService
     */
    protected ShareService $shareService;

    /**
     * ShareHook constructor.
     *
     * @param ShareService    $shareService
     * @param PasswordService $passwordService
     */
    public function __construct(ShareService $shareService, PasswordService $passwordService) {
        $this->passwordService = $passwordService;
        $this->shareService    = $shareService;
    }

    /**
     * @param Event $event
     *
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    public function handle(Event $event): void {
        if(!($event instanceof ShareDeletedEvent)) return;
        try {
            $share  = $event->getShare();
            $shares = $this->shareService->findBySourcePassword($share->getSourcePassword());

            if(empty($shares)) {
                /** @var Password $password */
                $password = $this->passwordService->findByUuid($share->getSourcePassword());
                $password->setHasShares(false);
                $this->passwordService->save($password);
            }
        } catch(DoesNotExistException $e) {
        }
    }
}
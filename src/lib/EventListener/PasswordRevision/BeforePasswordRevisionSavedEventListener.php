<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\EventListener\PasswordRevision;

use Exception;
use OCA\Passwords\Events\PasswordRevision\BeforePasswordRevisionCreatedEvent;
use OCA\Passwords\Events\PasswordRevision\BeforePasswordRevisionUpdatedEvent;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\PasswordSecurityCheckService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class BeforePasswordRevisionSavedEventListener
 *
 * @package OCA\Passwords\EventListener\PasswordRevision
 */
class BeforePasswordRevisionSavedEventListener implements IEventListener {

    /**
     * BeforePasswordRevisionSavedEventListener constructor.
     *
     * @param UserSettingsHelper           $userSettingsHelper
     * @param PasswordSecurityCheckService $passwordSecurityCheckService
     */
    public function __construct(
        protected UserSettingsHelper           $userSettingsHelper,
        protected PasswordSecurityCheckService $passwordSecurityCheckService
    ) {
    }

    /**
     * @param BeforePasswordRevisionCreatedEvent|BeforePasswordRevisionUpdatedEvent $event
     *
     * @throws Exception
     */
    public function handle(Event $event): void {
        $revision = $event->getPasswordRevision();

        $hashLength = $this->userSettingsHelper->get('password.security.hash', $revision->getUserId());
        if($hashLength === 0) {
            $revision->setHash('');
            $this->passwordSecurityCheckService->updateRevisionSecurityStatus($revision);

            return;
        }

        if(strlen($revision->getHash()) > $hashLength) {
            $revision->setHash(substr($revision->getHash(), 0, $hashLength));
            $this->passwordSecurityCheckService->updateRevisionSecurityStatus($revision);
        }
    }
}
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

use OCA\Passwords\Events\PasswordRevision\BeforePasswordRevisionCreatedEvent;
use OCA\Passwords\Events\PasswordRevision\BeforePasswordRevisionUpdatedEvent;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCP\EventDispatcher\Event;

/**
 * Class BeforePasswordRevisionSavedEventListener
 *
 * @package OCA\Passwords\EventListener\PasswordRevision
 */
class BeforePasswordRevisionSavedEventListener {

    /**
     * @var UserSettingsHelper
     */
    protected UserSettingsHelper $userSettingsHelper;

    /**
     * BeforePasswordRevisionSavedEventListener constructor.
     *
     * @param UserSettingsHelper $userSettingsHelper
     */
    public function __construct(UserSettingsHelper $userSettingsHelper) {
        $this->userSettingsHelper = $userSettingsHelper;
    }

    /**
     * @param BeforePasswordRevisionCreatedEvent|BeforePasswordRevisionUpdatedEvent $event
     *
     * @throws \Exception
     */
    public function handle(Event $event): void {
        $revision = $event->getPasswordRevision();

        $hashLength = $this->userSettingsHelper->get('password.security.hash', $revision->getUserId());
        if($hashLength === 0) {
            $revision->setHash('');
        } else if(strlen($revision->getHash()) > $hashLength) {
            $revision->setHash(substr($revision->getHash(), 0, $hashLength));
        }
    }
}
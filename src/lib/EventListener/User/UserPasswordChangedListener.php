<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\EventListener\User;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ISession;

class UserPasswordChangedListener implements IEventListener {

    public function __construct(protected ISession $session) {
    }

    /**
     * @param Event|\OCP\User\Events\PasswordUpdatedEvent  $event
     *
     * @return void
     */
    public function handle(Event $event): void {
        if($this->session->exists('login_credentials')) {
            $loginCredentials = json_decode($this->session->get('login_credentials'));
            $loginCredentials->password = $event->getPassword();
            $this->session->set('login_credentials', json_encode($loginCredentials));
        }
    }
}
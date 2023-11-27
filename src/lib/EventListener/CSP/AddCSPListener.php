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

namespace OCA\Passwords\EventListener\CSP;

use Exception;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IRequest;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class AddCSPListener implements IEventListener {

    public function __construct(
        protected IRequest $request
    ) {
    }

    /**
     * @param Event|AddContentSecurityPolicyEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function handle(Event $event): void {
        if(!$event instanceof AddContentSecurityPolicyEvent) {
            return;
        }

        if(!str_starts_with($this->request->getPathInfo(), '/apps/dashboard')) {
            return;
        }

        $csp = new EmptyContentSecurityPolicy();
        $csp->addAllowedScriptDomain($this->request->getServerHost());
        $csp->addAllowedConnectDomain('data:');
        $csp->allowEvalScript();
        $event->addPolicy($csp);
    }
}
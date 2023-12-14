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

namespace OCA\Passwords\Dashboard;

use OCA\Passwords\Helper\Token\ApiTokenHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\UserChallengeService;
use OCA\Passwords\Services\UserSettingsService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IConditionalWidget;
use OCP\Dashboard\IWidget;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Util;

class PasswordsWidget implements IWidget, IConditionalWidget {

    public function __construct(
        protected IL10N                $l10n,
        protected ConfigurationService $config,
        protected IRequest             $request,
        protected UserSettingsService  $settings,
        protected ApiTokenHelper       $tokenHelper,
        protected IURLGenerator        $urlGenerator,
        protected IInitialState        $initialState,
        protected UserChallengeService $challengeService,
        protected EnvironmentService   $environmentService
    ) {
    }

    public function getId(): string {
        return 'passwords-widget';
    }

    public function getTitle(): string {
        return $this->l10n->t('Passwords');
    }

    public function getOrder(): int {
        return 10;
    }

    public function getIconClass(): string {
        return 'icon-category-auth';
    }

    public function getUrl(): ?string {
        return $this->urlGenerator->linkToRouteAbsolute('passwords.page.index');
    }

    public function load(): void {
        if($this->request->urlParams['_route'] !== 'dashboard.dashboard.index') {
            return;
        }

        [$token, $user] = $this->tokenHelper->getWebUiToken();
        $this->initialState->provideInitialState('settings', $this->settings->list());
        $this->initialState->provideInitialState('api-user', $user);
        $this->initialState->provideInitialState('api-token', $token);
        $this->initialState->provideInitialState('authenticate', $this->challengeService->hasChallenge());
        $this->initialState->provideInitialState('impersonate', false);

        if($this->config->hasAppValue('dev/app/hash')) {
            Util::addScript('passwords', 'Static/dashboard.'.$this->config->getAppValue('dev/app/hash'));
        } else {
            Util::addScript('passwords', 'Static/dashboard');
        }
    }

    public function isEnabled(): bool {
        return $this->request->getServerProtocol() === 'https' &&
               !$this->environmentService->isImpersonating() ;
    }
}
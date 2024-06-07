<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Controller;

use Exception;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCA\Passwords\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCA\Passwords\Helper\Token\ApiTokenHelper;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\NotificationService;
use OCA\Passwords\Services\UserSettingsService;
use OCA\Passwords\Helper\Http\SetupReportHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\UserChallengeService;
use OCA\Passwords\Services\DeferredActivationService;

/**
 * Class PageController
 *
 * @package OCA\Passwords\Controller
 */
class PageController extends Controller {

    /**
     * @var UserSettingsService
     */
    protected UserSettingsService $settings;

    /**
     * @var ApiTokenHelper
     */
    protected ApiTokenHelper $tokenHelper;

    /**
     * @var EnvironmentService
     */
    protected EnvironmentService $environment;

    /**
     * @var NotificationService
     */
    protected NotificationService $notifications;

    /**
     * @var UserChallengeService
     */
    protected UserChallengeService $challengeService;

    /**
     * @var SetupReportHelper
     */
    protected SetupReportHelper $setupReportHelper;

    /**
     * @var DeferredActivationService
     */
    protected DeferredActivationService $das;

    /**
     * @var IInitialState
     */
    protected IInitialState        $initialState;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @param IRequest                  $request
     * @param ApiTokenHelper            $tokenHelper
     * @param IInitialState             $initialState
     * @param UserSettingsService       $settings
     * @param EnvironmentService        $environment
     * @param NotificationService       $notifications
     * @param SetupReportHelper         $setupReportHelper
     * @param UserChallengeService      $challengeService
     * @param DeferredActivationService $das
     * @param ConfigurationService      $config
     */
    public function __construct(
        IRequest                  $request,
        ApiTokenHelper            $tokenHelper,
        IInitialState             $initialState,
        UserSettingsService       $settings,
        EnvironmentService        $environment,
        NotificationService       $notifications,
        SetupReportHelper         $setupReportHelper,
        UserChallengeService      $challengeService,
        DeferredActivationService $das,
        ConfigurationService      $config,
    ) {
        parent::__construct(Application::APP_NAME, $request);
        $this->das = $das;
        $this->config = $config;
        $this->settings = $settings;
        $this->tokenHelper = $tokenHelper;
        $this->environment = $environment;
        $this->initialState = $initialState;
        $this->notifications = $notifications;
        $this->challengeService = $challengeService;
        $this->setupReportHelper = $setupReportHelper;
    }

    /**
     * @throws Exception
     */
    #[UseSession]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function index(): TemplateResponse {
        $isSecure = $this->checkIfHttpsUsed();

        if($isSecure) {
            $this->addHeaders();
            $this->checkImpersonation();
        } else {
            $this->tokenHelper->destroyWebUiToken();
        }

        $response = new TemplateResponse(
            $this->appName,
            'index',
            $this->getTemplateVariables($isSecure)
        );

        $this->getContentSecurityPolicy($response);

        $this->config->setAppValue('web/php/version/id', PHP_VERSION_ID);
        $this->config->setAppValue('web/php/version/string', phpversion());

        return $response;
    }

    /**
     * @return bool
     */
    protected function checkIfHttpsUsed(): bool {
        $httpsParam = $this->request->getParam('https', 'true') === 'true';

        return $this->request->getServerProtocol() === 'https' && $httpsParam;
    }

    /**
     *
     * @throws Exception
     */
    protected function addHeaders(): void {
        $this->initialState->provideInitialState('settings', $this->settings->list());

        [$token, $user] = $this->tokenHelper->getWebUiToken();
        $this->initialState->provideInitialState('api-user', $user);
        $this->initialState->provideInitialState('api-token', $token);

        $this->initialState->provideInitialState('authenticate', $this->challengeService->hasChallenge());
        $this->initialState->provideInitialState('impersonate', $this->environment->isImpersonating());
        $this->initialState->provideInitialState('features', $this->das->getClientFeatures());
    }

    /**
     * @param TemplateResponse $response
     *
     * @throws Exception
     */
    protected function getContentSecurityPolicy(TemplateResponse $response): void {
        $manualHost = parse_url($this->settings->get('server.handbook.url'), PHP_URL_HOST);

        $csp = $response->getContentSecurityPolicy();
        $csp->addAllowedScriptDomain($this->request->getServerHost());
        $csp->addAllowedConnectDomain($manualHost);
        $csp->addAllowedConnectDomain('data:');
        $csp->addAllowedImageDomain($manualHost);
        $csp->addAllowedMediaDomain($manualHost);
        $csp->addAllowedMediaDomain('blob:');
        $csp->allowInlineStyle();
        $csp->allowEvalWasm();

        $response->setContentSecurityPolicy($csp);
    }

    /**
     * @throws Exception
     */
    protected function checkImpersonation(): void {
        if($this->environment->isImpersonating()) {
            $this->notifications->sendImpersonationNotification(
                $this->environment->getUserId(),
                $this->environment->getRealUser()->getUID()
            );
        }
    }

    /**
     * @param bool $isSecure
     *
     * @return array[]
     */
    protected function getTemplateVariables(bool $isSecure): array {
        $variables = [
            'https' => $isSecure
        ];

        if(!$isSecure) {
            $variables['report'] = $this->setupReportHelper->getHttpsSetupReport();
        }
        if($this->config->hasAppValue('dev/app/hash')) {
            $variables['hash'] = $this->config->getAppValue('dev/app/hash');
        }

        return $variables;
    }
}

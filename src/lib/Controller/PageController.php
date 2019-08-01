<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Token\ApiTokenHelper;
use OCA\Passwords\Helper\User\UserTokenHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\NotificationService;
use OCA\Passwords\Services\UserChallengeService;
use OCA\Passwords\Services\UserSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\StrictContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

/**
 * Class PageController
 *
 * @package OCA\Passwords\Controller
 */
class PageController extends Controller {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var UserSettingsService
     */
    protected $settings;

    /**
     * @var ApiTokenHelper
     */
    protected $tokenHelper;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var NotificationService
     */
    protected $notifications;

    /**
     * @var UserTokenHelper
     */
    protected $userTokenHelper;

    /**
     * @var UserChallengeService
     */
    protected $challengeService;

    /**
     * PageController constructor.
     *
     * @param IRequest             $request
     * @param UserSettingsService  $settings
     * @param ApiTokenHelper       $tokenHelper
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     * @param UserTokenHelper      $userTokenHelper
     * @param NotificationService  $notifications
     * @param UserChallengeService $challengeService
     */
    public function __construct(
        IRequest $request,
        UserSettingsService $settings,
        ApiTokenHelper $tokenHelper,
        ConfigurationService $config,
        EnvironmentService $environment,
        UserTokenHelper $userTokenHelper,
        NotificationService $notifications,
        UserChallengeService $challengeService
    ) {
        parent::__construct(Application::APP_NAME, $request);
        $this->config           = $config;
        $this->tokenHelper      = $tokenHelper;
        $this->settings         = $settings;
        $this->environment      = $environment;
        $this->notifications    = $notifications;
        $this->userTokenHelper  = $userTokenHelper;
        $this->challengeService = $challengeService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @UseSession
     * @throws \Exception
     */
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
            ['https' => $isSecure]
        );

        $response->setContentSecurityPolicy($this->getContentSecurityPolicy());

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
     * @throws \Exception
     */
    protected function addHeaders(): void {
        $userSettings = json_encode($this->settings->list());
        Util::addHeader('meta', ['name' => 'settings', 'content' => $userSettings]);

        list($token, $user) = $this->tokenHelper->getWebUiToken();
        Util::addHeader('meta', ['name' => 'api-user', 'content' => $user]);
        Util::addHeader('meta', ['name' => 'api-token', 'content' => $token]);

        $authenticate = $this->challengeService->hasChallenge() || $this->userTokenHelper->hasToken() ? 'true':'false';
        Util::addHeader('meta', ['name' => 'pw-authenticate', 'content' => $authenticate]);

        $impersonate = $this->environment->isImpersonating() ? 'true':'false';
        Util::addHeader('meta', ['name' => 'pw-impersonate', 'content' => $impersonate]);
    }

    /**
     * @return StrictContentSecurityPolicy
     * @throws \Exception
     */
    protected function getContentSecurityPolicy(): StrictContentSecurityPolicy {
        $manualHost = parse_url($this->settings->get('server.handbook.url'), PHP_URL_HOST);
        $csp        = new StrictContentSecurityPolicy();
        $csp->addAllowedScriptDomain($this->request->getServerHost());
        $csp->addAllowedConnectDomain($manualHost);
        $csp->addAllowedConnectDomain('data:');
        $csp->addAllowedImageDomain($manualHost);
        $csp->addAllowedMediaDomain($manualHost);
        $csp->allowEvalScript();
        $csp->allowInlineStyle();

        return $csp;
    }

    /**
     * @throws \Exception
     */
    protected function checkImpersonation(): void {
        if($this->environment->isImpersonating()) {
            $this->notifications->sendImpersonationNotification(
                $this->environment->getUserId(),
                $this->environment->getRealUser()->getUID()
            );
        }
    }
}

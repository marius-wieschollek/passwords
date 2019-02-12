<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Token\ApiTokenHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\SessionService;
use OCA\Passwords\Services\SettingsService;
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
     * @var SessionService
     */
    protected $session;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var ApiTokenHelper
     */
    protected $tokenHelper;

    /**
     * @var SettingsService
     */
    protected $settingsService;

    /**
     * PageController constructor.
     *
     * @param IRequest             $request
     * @param ApiTokenHelper       $tokenHelper
     * @param ConfigurationService $config
     * @param SessionService       $sessionService
     * @param SettingsService      $settingsService
     */
    public function __construct(
        IRequest $request,
        ApiTokenHelper $tokenHelper,
        ConfigurationService $config,
        SessionService $sessionService,
        SettingsService $settingsService
    ) {
        parent::__construct(Application::APP_NAME, $request);
        $this->config          = $config;
        $this->tokenHelper     = $tokenHelper;
        $this->settingsService = $settingsService;
        $this->session         = $sessionService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @UseSession
     */
    public function index(): TemplateResponse {
        $isSecure = $this->checkIfHttpsUsed();
        if($isSecure) {
            $this->getUserSettings();
            list($token, $user) = $this->tokenHelper->getWebUiToken();
            Util::addHeader('meta', ['name' => 'api-user', 'content' => $user]);
            Util::addHeader('meta', ['name' => 'api-token', 'content' => $token]);
            Util::addHeader('meta', ['name' => 'api-session', 'content' => $this->session->getId()]);
        } else {
            $this->tokenHelper->destroyWebUiToken();
        }

        $response = new TemplateResponse(
            $this->appName,
            'index',
            ['https' => $isSecure]
        );
        $this->session->save();

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
     */
    protected function getUserSettings(): void {
        Util::addHeader(
            'meta',
            [
                'name'    => 'settings',
                'content' => json_encode($this->settingsService->list())
            ]
        );
    }

    /**
     * @return StrictContentSecurityPolicy
     */
    protected function getContentSecurityPolicy(): StrictContentSecurityPolicy {
        $manualHost = parse_url($this->settingsService->get('server.handbook.url'), PHP_URL_HOST);
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
}

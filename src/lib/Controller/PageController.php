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
use OCA\Passwords\Services\EnvironmentService;
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
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var SettingsService
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
     * PageController constructor.
     *
     * @param IRequest             $request
     * @param SettingsService      $settings
     * @param ApiTokenHelper       $tokenHelper
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     */
    public function __construct(
        IRequest $request,
        SettingsService $settings,
        ApiTokenHelper $tokenHelper,
        ConfigurationService $config,
        EnvironmentService $environment
    ) {
        parent::__construct(Application::APP_NAME, $request);
        $this->config      = $config;
        $this->tokenHelper = $tokenHelper;
        $this->settings    = $settings;
        $this->environment = $environment;
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
     */
    protected function addHeaders(): void {
        $userSettings = json_encode($this->settings->list());
        Util::addHeader('meta', ['name' => 'settings', 'content' => $userSettings]);

        list($token, $user) = $this->tokenHelper->getWebUiToken();
        Util::addHeader('meta', ['name' => 'api-user', 'content' => $user]);
        Util::addHeader('meta', ['name' => 'api-token', 'content' => $token]);

        if(!$this->environment->isImpersonating()) {
            Util::addHeader('meta', ['name' => 'pw-impersonate', 'content' => $this->environment->isImpersonating()]);
        }
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
}

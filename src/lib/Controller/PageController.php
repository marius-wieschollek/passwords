<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Token\TokenHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
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
     * @var TokenHelper
     */
    protected $tokenHelper;

    /**
     * @var SettingsService
     */
    protected $settingsService;

    /**
     * @var EnvironmentService
     */
    protected $environmentService;

    /**
     * PageController constructor.
     *
     * @param IRequest             $request
     * @param TokenHelper          $tokenHelper
     * @param ConfigurationService $config
     * @param SettingsService      $settingsService
     * @param EnvironmentService   $environmentService
     */
    public function __construct(
        IRequest $request,
        TokenHelper $tokenHelper,
        ConfigurationService $config,
        SettingsService $settingsService,
        EnvironmentService $environmentService
    ) {
        parent::__construct(Application::APP_NAME, $request);
        $this->config             = $config;
        $this->tokenHelper        = $tokenHelper;
        $this->settingsService    = $settingsService;
        $this->environmentService = $environmentService;
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
            Util::addHeader('meta', ['name' => 'api-user', 'content' => $this->environmentService->getUserLogin()]);
            Util::addHeader('meta', ['name' => 'api-token', 'content' => $this->tokenHelper->getWebUiToken()]);
        } else {
            $this->tokenHelper->destroyWebUiToken();
        }

        $response = new TemplateResponse(
            $this->appName,
            'index',
            [
                'https'       => $isSecure,
                'https_debug' => $this->config->getAppValue('debug/https', false)
            ]
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
     * @return ContentSecurityPolicy
     */
    protected function getContentSecurityPolicy(): ContentSecurityPolicy {
        $manualHost = parse_url($this->settingsService->get('server.handbook.url'), PHP_URL_HOST);
        $csp        = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain($this->request->getServerHost());
        $csp->addAllowedConnectDomain($manualHost);
        $csp->addAllowedImageDomain($manualHost);
        $csp->addAllowedMediaDomain($manualHost);

        return $csp;
    }
}

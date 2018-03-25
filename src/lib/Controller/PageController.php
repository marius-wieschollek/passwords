<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OC\AppFramework\Http\Request;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Token\TokenHelper;
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
     * @var TokenHelper
     */
    private $tokenHelper;

    /**
     * @var SettingsService
     */
    protected $settingsService;

    /**
     * PageController constructor.
     *
     * @param IRequest        $request
     * @param TokenHelper     $tokenHelper
     * @param SettingsService $settingsService
     */
    public function __construct(
        IRequest $request,
        TokenHelper $tokenHelper,
        SettingsService $settingsService
    ) {
        parent::__construct(Application::APP_NAME, $request);
        $this->tokenHelper     = $tokenHelper;
        $this->settingsService = $settingsService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {

        $isSecure = $this->checkIfHttpsUsed();
        if($isSecure) {
            $this->getUserSettings();
            //$this->includeBrowserPolyfills();
            Util::addHeader('meta', ['name' => 'pwat', 'content' => $this->tokenHelper->getWebUiToken()]);
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
     *
     */
    protected function includeBrowserPolyfills(): void {
        if($this->request->isUserAgent([Request::USER_AGENT_MS_EDGE])) {
            Util::addScript(Application::APP_NAME, 'Static/Polyfill/TextEncoder/encoding');
            Util::addScript(Application::APP_NAME, 'Static/Polyfill/TextEncoder/encoding-indexes');
        };
    }

    /**
     * @return ContentSecurityPolicy
     */
    protected function getContentSecurityPolicy(): ContentSecurityPolicy {
        $manualHost = parse_url($this->settingsService->get('server.manual.url'), PHP_URL_HOST);
        $csp        = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain($this->request->getServerHost());
        $csp->addAllowedConnectDomain($manualHost);
        $csp->addAllowedImageDomain($manualHost);

        return $csp;
    }
}

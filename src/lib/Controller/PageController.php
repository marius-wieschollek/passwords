<?php

namespace OCA\Passwords\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

/**
 * Class PageController
 *
 * @package OCA\Passwords\Controller
 */
class PageController extends Controller {

    /**
     * PageController constructor.
     *
     * @param string   $appName
     * @param IRequest $request
     */
    public function __construct($appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        return new TemplateResponse(
            $this->appName,
            'index',
            ['https' => $this->checkIfHttpsUsed()]
        );
    }

    /**
     * @return bool
     */
    protected function checkIfHttpsUsed(): bool {
        $config      = \OC::$server->getConfig();
        $forceSsl    = $config->getSystemValue('forcessl', false);
        $protocol    = $config->getSystemValue('overwriteprotocol', '');
        $ignoreHttps = $config->getAppValue('passwords', 'environment', 'production') === 'dev';

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] === 443 || $protocol === 'https' || $forceSsl || $ignoreHttps;
    }
}

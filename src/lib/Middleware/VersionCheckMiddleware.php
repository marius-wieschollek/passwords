<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Middleware;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\ConfigurationService;
use OCP\AppFramework\Middleware;
use OCP\ILogger;

/**
 * Class VersionCheckMiddleware
 *
 * @package OCA\Passwords\Middleware
 */
class VersionCheckMiddleware extends Middleware {

    const CURRENT_DATABASE_VERSION = 1;

    /**
     * @var ILogger
     */
    protected $logger;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * ApiSecurityMiddleware constructor.
     *
     * @param ILogger              $logger
     * @param ConfigurationService $config
     */
    public function __construct(ILogger $logger, ConfigurationService $config) {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @param \OCP\AppFramework\Controller $controller
     * @param string                       $methodName
     *
     * @throws ApiException
     */
    public function beforeController($controller, $methodName): void {
        $lastVersion = $this->config->getAppValue('last_version', '2018.5.2');

        if(intval($this->config->getAppValue('database_version', 0)) !== self::CURRENT_DATABASE_VERSION) {
            $this->config->setAppValue('installed_version', $lastVersion);
            \OC_Util::redirectToDefaultPage();
        }

        $installedVersion = $this->config->getAppValue('installed_version');
        if($lastVersion !== $installedVersion) {
            $this->config->setAppValue('last_version', $installedVersion);
        }

        parent::beforeController($controller, $methodName);
    }

}
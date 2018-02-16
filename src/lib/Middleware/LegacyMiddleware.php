<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Middleware;

use OCA\Passwords\Controller\Api\Legacy\LegacyCategoryApiController;
use OCA\Passwords\Controller\Api\Legacy\LegacyPasswordApiController;
use OCA\Passwords\Controller\Api\Legacy\LegacyVersionApiController;
use OCA\Passwords\Services\ConfigurationService;
use OCP\AppFramework\Middleware;

/**
 * Class LegacyMiddleware
 *
 * @package OCA\Passwords\Middleware
 */
class LegacyMiddleware extends Middleware {

    protected $legacyControllers
        = [
            LegacyCategoryApiController::class,
            LegacyPasswordApiController::class,
            LegacyVersionApiController::class
        ];

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * LegacyMiddleware constructor.
     *
     * @param ConfigurationService $config
     */
    public function __construct(ConfigurationService $config) {
        $this->config = $config;
    }

    /**
     * @param \OCP\AppFramework\Controller $controller
     * @param string                       $methodName
     */
    public function beforeController($controller, $methodName): void {

        if(in_array(get_class($controller), $this->legacyControllers)) {
            $this->config->setAppValue('legacy_last_used', time());
        }

        parent::beforeController($controller, $methodName);
    }
}
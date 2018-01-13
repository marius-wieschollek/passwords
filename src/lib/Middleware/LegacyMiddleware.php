<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.01.18
 * Time: 19:54
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
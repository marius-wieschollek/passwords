<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.01.18
 * Time: 21:28
 */

namespace OCA\Passwords\Middleware;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\ConfigurationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\ILogger;

/**
 * Class ApiSecurityMiddleware
 *
 * @package OCA\Passwords\Middleware
 */
class ApiSecurityMiddleware extends Middleware {

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

        if(substr(get_class($controller), 0, 28) === 'OCA\Passwords\Controller\Api') {
            if(!$this->checkIfHttpsUsed()) throw new ApiException('HTTPS required', 400);
        }

        parent::beforeController($controller, $methodName);
    }

    /**
     * @param \OCP\AppFramework\Controller $controller
     * @param string                       $methodName
     * @param \Exception                   $exception
     *
     * @return null|JSONResponse
     */
    public function afterException($controller, $methodName, \Exception $exception): ?JSONResponse {
        if(substr(get_class($controller), 0, 28) !== 'OCA\Passwords\Controller\Api') {
            return null;
        }

        $message    = 'Unable to complete request';
        $id         = 0;
        $statusCode = Http::STATUS_SERVICE_UNAVAILABLE;

        $this->logger->logException($exception, ['app' => Application::APP_NAME]);

        if(get_class($exception) === ApiException::class || is_subclass_of($exception, ApiException::class)) {
            /** @var ApiException $exception */
            $id         = $exception->getId();
            $message    = $exception->getMessage();
            $statusCode = $exception->getHttpCode();
        }

        if(get_class($exception) === DoesNotExistException::class) {
            $id         = 404;
            $message    = 'Resource not found';
            $statusCode = 404;
        }

        $response = new JSONResponse(
            [
                'status'  => 'error',
                'id'      => $id,
                'message' => $message
            ], $statusCode
        );

        return $response;
    }

    /**
     * @return bool
     */
    protected function checkIfHttpsUsed(): bool {
        $forceSsl    = $this->config->getSystemValue('forcessl', false);
        $protocol    = $this->config->getSystemValue('overwriteprotocol', '');
        $ignoreHttps = $this->config->getAppValue('environment', 'production') === 'dev';

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] === 443 || $protocol === 'https' || $forceSsl || $ignoreHttps;
    }
}
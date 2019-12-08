<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Middleware;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\LoggingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

/**
 * Class ApiSecurityMiddleware
 *
 * @package OCA\Passwords\Middleware
 */
class ApiSecurityMiddleware extends Middleware {

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var IRequest
     */
    protected $request;

    /**
     * ApiSecurityMiddleware constructor.
     *
     * @param LoggingService $logger
     * @param IRequest       $request
     */
    public function __construct(LoggingService $logger, IRequest $request) {
        $this->logger  = $logger;
        $this->request = $request;
    }

    /**
     * @param Controller $controller
     * @param string     $methodName
     *
     * @throws ApiException
     */
    public function beforeController($controller, $methodName): void {

        if($this->isApiClass($controller) && $this->request->getServerProtocol() !== 'https') {
            throw new ApiException('HTTPS required', 400);
        }

        parent::beforeController($controller, $methodName);
    }

    /**
     * @param Controller $controller
     * @param string     $methodName
     * @param \Exception $exception
     *
     * @return JSONResponse
     * @throws \Exception
     */
    public function afterException($controller, $methodName, \Exception $exception): JSONResponse {
        if(!$this->isApiClass($controller)) throw $exception;

        $message    = 'Unable to complete request';
        $id         = 0;
        $statusCode = Http::STATUS_SERVICE_UNAVAILABLE;

        $this->logger->error(['Error "%1$s" in %2$s::%3$s', $exception->getMessage(), get_class($controller), $methodName]);
        $this->logger->logException($exception);

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
     * @param $object
     *
     * @return bool
     */
    protected function isApiClass($object): bool {
        $class = get_class($object);

        return substr($class, 0, 28) === 'OCA\Passwords\Controller\Api' ||
               substr($class, 0, 30) === 'OCA\Passwords\Controller\Admin';
    }
}
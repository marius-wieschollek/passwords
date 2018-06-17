<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Middleware;

use OCA\Passwords\Services\SessionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

/**
 * Class ApiSessionMiddleware
 *
 * @package OCA\Passwords\Middleware
 */
class ApiSessionMiddleware extends Middleware {

    /**
     * @var IRequest
     */
    protected $request;

    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * ApiSessionMiddleware constructor.
     *
     * @param IRequest       $request
     * @param SessionService $sessionService
     */
    public function __construct(IRequest $request, SessionService $sessionService) {
        $this->sessionService = $sessionService;
    }

    /**
     * @param Controller $controller
     * @param string     $methodName
     * @param Response   $response
     *
     * @return Response
     */
    public function afterController($controller, $methodName, Response $response): Response {
        if(!$this->isApiRequest($controller)) return $response;

        if($this->sessionService->isSessionChanged()) {
            $this->sessionService->save();
            $response->addHeader('X-Passwords-Session', $this->sessionService->getId());
        }

        return parent::afterController($controller, $methodName, $response);
    }

    /**
     * @param Controller $controller
     *
     * @return bool
     */
    protected function isApiRequest(Controller $controller): bool {
        return substr(get_class($controller), 0, 28) == 'OCA\Passwords\Controller\Api';
    }
}
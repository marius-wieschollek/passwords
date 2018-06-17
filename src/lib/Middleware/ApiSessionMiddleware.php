<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Middleware;

use OCA\Passwords\Db\Session;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\Object\SessionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\ISession;

/**
 * Class ApiSessionMiddleware
 *
 * @package OCA\Passwords\Middleware
 */
class ApiSessionMiddleware extends \OCP\AppFramework\Middleware {

    /**
     * @var IRequest
     */
    protected $request;

    /**
     * @var ISession
     */
    protected $session;

    /**
     * @var Session
     */
    protected $apiSession;

    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * @var EnvironmentService
     */
    protected $environmentService;

    /**
     * @var array
     */
    protected $sessionKeys = [
        'account/reset/request/time',
        'security/sse/v2/key'
    ];

    /**
     * ApiSessionMiddleware constructor.
     *
     * @param IRequest       $request
     * @param ISession       $session
     * @param SessionService $sessionService
     */
    public function __construct(IRequest $request, ISession $session, SessionService $sessionService, EnvironmentService $environmentService) {
        $this->request = $request;
        $this->session = $session;
        $this->sessionService = $sessionService;
        $this->environmentService = $environmentService;
    }

    /**
     * @param Controller $controller
     * @param string     $methodName
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function beforeController($controller, $methodName): void {
        if(!$this->isApiRequest($controller)) return;

        $sessionToken = $this->request->getHeader('X-Passwords-Session');
        if($sessionToken) {
            $this->loadSession($sessionToken);
        } else {
            $this->apiSession = $this->sessionService->create();
        }

        parent::beforeController($controller, $methodName);
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

        $this->apiSession->setData('{"test":true}');
        $this->sessionService->save($this->apiSession);

        $response->addHeader('X-Passwords-Session', $this->apiSession->getUuid());

        return parent::afterController($controller, $methodName, $response);
    }

    /**
     * @param string $token
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    protected function loadSession(string $token) {
        $session = $this->sessionService->findByUuid($token);

        if($this->environmentService->getUserId() !== $session->getUserId()) {
            $this->sessionService->delete($session);
            throw new ApiException('Invalid Session Id');
        }

        if(time() > $session->getUpdated() + 15 * 60) {
            $this->sessionService->delete($session);
            throw new ApiException('Invalid Session Id');
        }

        $data = json_decode($session->getData(), true);
        foreach($data as $key => $value) {
            $this->session->set($key, $value);
        }

        $this->apiSession = $session;
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
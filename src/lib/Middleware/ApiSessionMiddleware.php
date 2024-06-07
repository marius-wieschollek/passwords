<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Middleware;

use OCA\Passwords\Controller\Api\ServiceApiController;
use OCA\Passwords\Controller\Api\SessionApiController;
use OCA\Passwords\Controller\Api\SettingsApiController;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\SessionService;
use OCA\Passwords\Services\UserChallengeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\DB\Exception;

/**
 * Class ApiSessionMiddleware
 *
 * @package OCA\Passwords\Middleware
 */
class ApiSessionMiddleware extends Middleware {

    /**
     * ApiSessionMiddleware constructor.
     *
     * @param SessionService       $sessionService
     * @param UserChallengeService $challengeService
     */
    public function __construct(
        protected SessionService       $sessionService,
        protected UserChallengeService $challengeService
    ) {
    }

    /**
     * @param Controller $controller
     * @param string     $methodName
     *
     * @throws ApiException
     */
    public function beforeController(Controller $controller, string $methodName): void {
        if(!$this->isApiRequest($controller)) return;

        $this->sessionService->load();
        if(!$this->sessionService->isAuthorized() && $this->requiresAuthorization($controller, $methodName)) {
            throw new ApiException('Authorized session required', Http::STATUS_PRECONDITION_FAILED);
        }

        parent::beforeController($controller, $methodName);
    }

    /**
     * @param Controller $controller
     * @param string     $methodName
     * @param Response   $response
     *
     * @return Response
     * @throws Exception
     */
    public function afterController(Controller $controller, string $methodName, Response $response): Response {
        if(!$this->isApiRequest($controller) || $response instanceof FileDisplayResponse) return $response;

        $this->sessionService->save();
        $sessionId = $this->sessionService->getEncryptedId();
        if($sessionId) {
            $response->addHeader(SessionService::API_SESSION_HEADER, $sessionId);
            $response->addCookie(SessionService::API_SESSION_COOKIE, $sessionId);
        }

        return parent::afterController($controller, $methodName, $response);
    }

    /**
     * @param Controller $controller
     *
     * @return bool
     */
    protected function isApiRequest(Controller $controller): bool {
        $class = get_class($controller);

        return str_starts_with($class, 'OCA\Passwords\Controller\Api');
    }

    /**
     * @param Controller $controller
     * @param string     $method
     *
     * @return bool
     */
    protected function requiresAuthorization(Controller $controller, string $method): bool {

        if(!$this->challengeService->hasChallenge()) {
            return false;
        }

        if($controller instanceof SessionApiController && in_array($method, ['open', 'request', 'requestToken', 'keepAlive'])) {
            return false;
        }

        if($controller instanceof ServiceApiController && in_array($method, ['getAvatar', 'getFavicon', 'getPreview', 'getHashes'])) {
            return false;
        }

        if($controller instanceof SettingsApiController && in_array($method, ['get', 'list'])) {
            return false;
        }

        return true;
    }
}
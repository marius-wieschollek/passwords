<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Helper\User\UserPasswordHelper;
use OCA\Passwords\Helper\User\UserTokenHelper;
use OCA\Passwords\Services\SessionService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class SessionApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class SessionApiController extends AbstractApiController {

    /**
     * @var SessionService
     */
    protected $session;

    /**
     * @var UserTokenHelper
     */
    protected $tokenHelper;

    /**
     * @var UserPasswordHelper
     */
    protected $passwordHelper;

    /**
     * SessionApiController constructor.
     *
     * @param IRequest           $request
     * @param UserTokenHelper    $tokenHelper
     * @param UserPasswordHelper $passwordHelper
     * @param SessionService     $session
     */
    public function __construct(IRequest $request, UserTokenHelper $tokenHelper, UserPasswordHelper $passwordHelper, SessionService $session) {
        parent::__construct($request);
        $this->tokenHelper    = $tokenHelper;
        $this->passwordHelper = $passwordHelper;
        $this->session        = $session;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     */
    public function request(): JSONResponse {

        $requirements = [];
        if($this->passwordHelper->hasPassword()) {
            $requirements['password'] = 'SHA-256';
        }

        $providers = $this->tokenHelper->getProvidersAsArray();
        if(!empty($providers)) {
            $requirements['token'] = $providers;
        }

        return new JSONResponse($requirements);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     * @UserRateThrottle(limit=4, period=60)
     *
     * @return JSONResponse
     */
    public function open(): JSONResponse {
        $parameters = $this->getParameterArray();

        $password = null;
        if($this->passwordHelper->hasPassword() && (!isset($parameters['password']) || !$this->passwordHelper->validatePassword($parameters['password']))) {
            return new JSONResponse(['success' => false], Http::STATUS_FORBIDDEN);
        } else if($this->passwordHelper->hasPassword()) {
            $password = $parameters['password'];
        }

        if($this->tokenHelper->tokenRequired() && (!isset($parameters['token']) || !$this->tokenHelper->verifyTokens($parameters['token']))) {
            return new JSONResponse(['success' => false], Http::STATUS_FORBIDDEN);
        }

        $this->session->authorizeSession($password);

        return new JSONResponse(['success' => true, 'keychain' => []], Http::STATUS_OK);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     * @UserRateThrottle(limit=3, period=60)
     *
     * @param $provider
     *
     * @return JSONResponse
     */
    public function requestToken($provider): JSONResponse {
        list($result, $data) = $this->tokenHelper->triggerProvider($provider);

        return new JSONResponse(['success' => $result, 'data' => $data], Http::STATUS_OK);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function close() {
        $this->session->delete();

        return new JSONResponse(['success' => true], Http::STATUS_OK);
    }
}
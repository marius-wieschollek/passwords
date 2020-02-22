<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\User\UserLoginAttemptHelper;
use OCA\Passwords\Helper\User\UserTokenHelper;
use OCA\Passwords\Services\Object\KeychainService;
use OCA\Passwords\Services\SessionService;
use OCA\Passwords\Services\UserChallengeService;
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
     * @var UserChallengeService
     */
    protected $challengeService;

    /**
     * @var KeychainService
     */
    protected $keychainService;

    /**
     * @var UserLoginAttemptHelper
     */
    protected $loginAttempts;

    /**
     * SessionApiController constructor.
     *
     * @param IRequest               $request
     * @param SessionService         $session
     * @param UserTokenHelper        $tokenHelper
     * @param KeychainService        $keychainService
     * @param UserLoginAttemptHelper $loginAttempts
     * @param UserChallengeService   $challengeService
     */
    public function __construct(
        IRequest $request,
        SessionService $session,
        UserTokenHelper $tokenHelper,
        KeychainService $keychainService,
        UserLoginAttemptHelper $loginAttempts,
        UserChallengeService $challengeService
    ) {
        parent::__construct($request);
        $this->session          = $session;
        $this->tokenHelper      = $tokenHelper;
        $this->loginAttempts    = $loginAttempts;
        $this->keychainService  = $keychainService;
        $this->challengeService = $challengeService;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     * @throws \Exception
     */
    public function request(): JSONResponse {
        if(!$this->loginAttempts->isAttemptAllowed()) {
            throw new ApiException('Login not allowed', Http::STATUS_FORBIDDEN);
        }

        $requirements = new \stdClass();
        if(!$this->session->isAuthorized()) {
            if($this->challengeService->hasChallenge()) {
                $requirements->challenge = $this->challengeService->getChallengeData();

                if($this->tokenHelper->hasToken()) {
                    $requirements->token = $this->tokenHelper->getProvidersAsArray();
                }
            }
        }

        return new JSONResponse($requirements);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     * @UserRateThrottle(limit=6, period=60)
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \Exception
     */
    public function open(): JSONResponse {
        if(!$this->loginAttempts->isAttemptAllowed()) {
            throw new ApiException('Login not allowed', Http::STATUS_FORBIDDEN);
        }

        if(!$this->session->isAuthorized()) {
            $parameters = $this->getParameterArray();
            $this->verifyToken($parameters);
            $this->verifyChallenge($parameters);
            $this->session->authorizeSession();
        }
        $this->loginAttempts->registerSuccessfulAttempt();

        return new JSONResponse(['success' => true, 'keys' => $this->keychainService->getClientKeychainArray()], Http::STATUS_OK);
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
     * @throws \ReflectionException
     */
    public function requestToken($provider): JSONResponse {
        list($result, $data) = $this->tokenHelper->triggerProvider($provider);

        return new JSONResponse(['success' => $result, 'data' => $data], $result ? Http::STATUS_OK:Http::STATUS_BAD_REQUEST);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function keepAlive() {
        return new JSONResponse(['success' => true], Http::STATUS_OK);
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

    /**
     * @param $parameters
     *
     * @throws ApiException
     */
    protected function verifyToken($parameters): void {
        if($this->challengeService->hasChallenge() && $this->tokenHelper->hasToken()) {
            if(!isset($parameters['token'])) {
                $this->loginAttempts->registerFailedAttempt();
                throw new ApiException('Token invalid', Http::STATUS_UNAUTHORIZED);
            }
            if(!$this->tokenHelper->verifyTokens($parameters['token'])) {
                $this->loginAttempts->registerFailedAttempt();
                throw new ApiException('Token verification failed', Http::STATUS_UNAUTHORIZED);
            }
        }
    }

    /**
     * @param $parameters
     *
     * @throws ApiException
     */
    protected function verifyChallenge($parameters): void {
        if($this->challengeService->hasChallenge()) {
            if(!isset($parameters['challenge'])) {
                $this->loginAttempts->registerFailedAttempt();
                throw new ApiException('Password invalid', Http::STATUS_UNAUTHORIZED);
            }
            try {
                if(!$this->challengeService->validateChallenge($parameters['challenge'])) {
                    throw new ApiException('Password verification failed');
                }
            } catch(ApiException $e) {
                if($e->getId() === 'a361c427') $this->loginAttempts->registerFailedAttempt();
                throw $e;
            }
        }
    }
}
<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Controller\Api;

use Exception;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\User\UserLoginAttemptHelper;
use OCA\Passwords\Helper\User\UserTokenHelper;
use OCA\Passwords\Services\DeferredActivationService;
use OCA\Passwords\Services\Object\KeychainService;
use OCA\Passwords\Services\SessionService;
use OCA\Passwords\Services\UserChallengeService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use ReflectionException;
use stdClass;

/**
 * Class SessionApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class SessionApiController extends AbstractApiController {

    /**
     * @var SessionService
     */
    protected SessionService $session;

    /**
     * @var UserTokenHelper
     */
    protected UserTokenHelper $tokenHelper;

    /**
     * @var UserChallengeService
     */
    protected UserChallengeService $challengeService;

    /**
     * @var KeychainService
     */
    protected KeychainService $keychainService;

    /**
     * @var UserLoginAttemptHelper
     */
    protected UserLoginAttemptHelper $loginAttempts;
    /**
     * @var DeferredActivationService
     */
    protected DeferredActivationService $das;

    /**
     * SessionApiController constructor.
     *
     * @param IRequest                  $request
     * @param SessionService            $session
     * @param UserTokenHelper           $tokenHelper
     * @param DeferredActivationService $das
     * @param KeychainService           $keychainService
     * @param UserLoginAttemptHelper    $loginAttempts
     * @param UserChallengeService      $challengeService
     */
    public function __construct(
        IRequest $request,
        SessionService $session,
        UserTokenHelper $tokenHelper,
        DeferredActivationService $das,
        KeychainService $keychainService,
        UserLoginAttemptHelper $loginAttempts,
        UserChallengeService $challengeService
    ) {
        parent::__construct($request);
        $this->das              = $das;
        $this->session          = $session;
        $this->tokenHelper      = $tokenHelper;
        $this->loginAttempts    = $loginAttempts;
        $this->keychainService  = $keychainService;
        $this->challengeService = $challengeService;
    }

    /**
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function request(): JSONResponse {
        if(!$this->loginAttempts->isAttemptAllowed()) {
            throw new ApiException('Login not allowed', Http::STATUS_FORBIDDEN);
        }

        $requirements = new stdClass();
        if(!$this->session->isAuthorized()) {
            if($this->challengeService->hasChallenge()) {
                $requirements->challenge = $this->challengeService->getChallengeData();

                if($this->tokenHelper->hasToken() && $this->das->check('two-factor-enabled', true)) {
                    $requirements->token = $this->tokenHelper->getProvidersAsArray();
                }
            }
        }

        return new JSONResponse($requirements);
    }

    /**
     * @return JSONResponse
     * @throws ApiException
     * @throws Exception
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[UserRateLimit(limit: 10, period: 30)]
    #[BruteForceProtection(action: 'passwords-login')]
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

        try {
            return new JSONResponse(['success' => true, 'keys' => $this->keychainService->getClientKeychainArray()], Http::STATUS_OK);
        } catch(\Throwable $e) {
            $this->session->delete();
            throw new ApiException('Reading user keychain failed', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * @param $provider
     *
     * @return JSONResponse
     * @throws ReflectionException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[UserRateLimit(limit: 3, period: 60)]
    public function requestToken($provider): JSONResponse {
        [$result, $data] = $this->tokenHelper->triggerProvider($provider);

        return new JSONResponse(['success' => $result, 'data' => $data], $result ? Http::STATUS_OK:Http::STATUS_BAD_REQUEST);
    }

    /**
     * @return JSONResponse
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function keepAlive(): JSONResponse {
        return new JSONResponse(['success' => true], Http::STATUS_OK);
    }

    /**
     * @return JSONResponse
     * @throws \OCP\DB\Exception
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function close(): JSONResponse {
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
                if(!$this->das->check('two-factor-required', true)) return;
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
                throw new ApiException('Passphrase invalid', Http::STATUS_UNAUTHORIZED);
            }
            try {
                if(!$this->challengeService->validateChallenge($parameters['challenge'])) {
                    throw new ApiException('Passphrase verification failed');
                }
            } catch(ApiException $e) {
                if($e->getId() === 'ab6e13ba') $this->loginAttempts->registerFailedAttempt();
                throw $e;
            }
        }
    }
}
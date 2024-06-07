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
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Services\DeferredActivationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\SessionService;
use OCA\Passwords\Services\UserChallengeService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;

/**
 * Class AccountApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class AccountApiController extends AbstractApiController {

    /**
     * @var IUserManager
     */
    protected IUserManager $userManager;

    /**
     * @var SessionService
     */
    protected SessionService $sessionService;

    /**
     * @var EnvironmentService
     */
    protected EnvironmentService $environment;

    /**
     * @var UserChallengeService
     */
    protected UserChallengeService $challengeService;

    /**
     * @var DeleteUserDataHelper
     */
    protected DeleteUserDataHelper $deleteUserDataHelper;

    /**
     * @var DeferredActivationService
     */
    protected DeferredActivationService $deferredActivation;
    /**
     * @var ISecureRandom
     */
    protected ISecureRandom $secureRandom;

    /**
     * AccountApiController constructor.
     *
     * @param IRequest                  $request
     * @param IUserManager              $userManager
     * @param ISecureRandom             $secureRandom
     * @param SessionService            $sessionService
     * @param EnvironmentService        $environment
     * @param UserChallengeService      $challengeService
     * @param DeleteUserDataHelper      $deleteUserDataHelper
     * @param DeferredActivationService $deferredActivation
     */
    public function __construct(
        IRequest $request,
        IUserManager $userManager,
        ISecureRandom $secureRandom,
        SessionService $sessionService,
        EnvironmentService $environment,
        UserChallengeService $challengeService,
        DeleteUserDataHelper $deleteUserDataHelper,
        DeferredActivationService $deferredActivation
    ) {
        parent::__construct($request);
        $this->userManager          = $userManager;
        $this->deleteUserDataHelper = $deleteUserDataHelper;
        $this->sessionService       = $sessionService;
        $this->environment          = $environment;
        $this->challengeService     = $challengeService;
        $this->deferredActivation   = $deferredActivation;
        $this->secureRandom         = $secureRandom;
    }

    /**
     * @param string|null $code
     *
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function reset(?string $code): JSONResponse {
        if($code !== null) {
            if($code === $this->sessionService->get('reset/code')) {
                $this->sessionService->unset('reset/code');
                $this->deleteUserDataHelper->deleteUserData($this->environment->getUserId());
                $this->sessionService->delete();

                return $this->createJsonResponse(['status' => 'ok']);
            }

            throw new ApiException('Invalid reset code', Http::STATUS_FORBIDDEN);
        }

        $code = [];
        for($i=0; $i<4; $i++) $code[] = $this->secureRandom->generate(4);
        $code = implode('-', $code);
        $this->sessionService->set('reset/code', $code);

        return $this->createJsonResponse(['status' => 'accepted', 'code' => $code], Http::STATUS_ACCEPTED);
    }

    /**
     * @return JSONResponse
     * @throws Exception
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getChallenge(): JSONResponse {
        return $this->createJsonResponse($this->challengeService->getChallengeData(), Http::STATUS_OK);
    }

    /**
     * @param null|string $secret
     * @param array       $data
     * @param null        $oldSecret
     *
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function setChallenge(string $secret, array $data, $oldSecret = null): JSONResponse {
        if($this->challengeService->hasChallenge()) {
            if($oldSecret === null) throw new ApiException('Passphrase invalid', Http::STATUS_UNAUTHORIZED);
            if(!$this->challengeService->validateChallenge($oldSecret)) {
                throw new ApiException('Passphrase verification failed');
            }
        }

        if($this->challengeService->setChallenge($data, $secret)) {
            $this->sessionService->authorizeSession();

            return $this->createJsonResponse(['success' => true]);
        }

        throw new ApiException('Challenge update failed');
    }
}
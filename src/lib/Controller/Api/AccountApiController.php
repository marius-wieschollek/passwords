<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserManager;

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
     * AccountApiController constructor.
     *
     * @param IRequest                  $request
     * @param IUserManager              $userManager
     * @param SessionService            $sessionService
     * @param EnvironmentService        $environment
     * @param UserChallengeService      $challengeService
     * @param DeleteUserDataHelper      $deleteUserDataHelper
     * @param DeferredActivationService $deferredActivation
     */
    public function __construct(
        IRequest $request,
        IUserManager $userManager,
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
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param $password
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws Exception
     */
    public function reset(string $password): JSONResponse {
        if(!$this->userManager->checkPassword($this->environment->getUserLogin(), $password)) {
            throw new ApiException('Password invalid', Http::STATUS_FORBIDDEN);
        }

        $time    = $this->sessionService->get('reset/time', 0);
        $current = time();
        if($current >= $time && $current - $time < 60) {
            $this->sessionService->unset('reset/time');
            $this->deleteUserDataHelper->deleteUserData($this->environment->getUserId());
            $this->sessionService->delete();

            return $this->createJsonResponse(['status' => 'ok'], Http::STATUS_OK);
        }

        $timeout = rand(5, 10);
        $this->sessionService->set('reset/time', $current + $timeout);

        return $this->createJsonResponse(['status' => 'accepted', 'wait' => $timeout], Http::STATUS_ACCEPTED);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     * @throws Exception
     */
    public function getChallenge(): JSONResponse {
        return $this->createJsonResponse($this->challengeService->getChallengeData(), Http::STATUS_OK);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param null|string $secret
     * @param array       $data
     * @param null        $oldSecret
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function setChallenge(string $secret, array $data, $oldSecret = null): JSONResponse {
        if(!$this->deferredActivation->check('client-side-encryption')) {
            throw new ApiException('Feature not enabled');
        }

        if($this->challengeService->hasChallenge()) {
            if($oldSecret === null) throw new ApiException('Password invalid', Http::STATUS_UNAUTHORIZED);
            if(!$this->challengeService->validateChallenge($oldSecret)) {
                throw new ApiException('Password verification failed');
            }
        }

        if($this->challengeService->setChallenge($data, $secret)) {
            $this->sessionService->authorizeSession();
            return $this->createJsonResponse(['success' => true], Http::STATUS_OK);
        }

        throw new ApiException('Password update failed');
    }
}
<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Helper\User\UserChallengeHelper;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\SessionService;
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
    protected $userManager;

    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var UserChallengeHelper
     */
    protected $challengeHelper;

    /**
     * @var DeleteUserDataHelper
     */
    protected $deleteUserDataHelper;

    /**
     * AccountApiController constructor.
     *
     * @param IRequest             $request
     * @param IUserManager         $userManager
     * @param SessionService       $sessionService
     * @param EnvironmentService   $environment
     * @param DeleteUserDataHelper $deleteUserDataHelper
     */
    public function __construct(
        IRequest $request,
        IUserManager $userManager,
        SessionService $sessionService,
        EnvironmentService $environment,
        UserChallengeHelper $passwordHelper,
        DeleteUserDataHelper $deleteUserDataHelper
    ) {
        parent::__construct($request);
        $this->userManager          = $userManager;
        $this->deleteUserDataHelper = $deleteUserDataHelper;
        $this->sessionService       = $sessionService;
        $this->environment          = $environment;
        $this->challengeHelper      = $passwordHelper;
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
     * @throws \Exception
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
     */
    public function getChallenge(): JSONResponse {
        if($this->challengeHelper->hasChallenge()) {
            return $this->createJsonResponse(['challenge' => $this->challengeHelper->getChallenge()], Http::STATUS_OK);
        }

        return $this->createJsonResponse([], Http::STATUS_NOT_FOUND);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param null|string $challenge
     * @param null|string $secret
     * @param null        $oldSecret
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function setChallenge(string $challenge, string $secret, $oldSecret = null): JSONResponse {
        if($this->challengeHelper->hasChallenge() && ($oldSecret === null || !$this->challengeHelper->validateChallenge($oldSecret))) {
            throw new ApiException('Invalid password', Http::STATUS_BAD_REQUEST);
        }

        if($this->challengeHelper->setChallenge($challenge, $secret)) {
            return $this->createJsonResponse(['success' => true], Http::STATUS_OK);
        }

        return $this->createJsonResponse(['success' => false], Http::STATUS_INTERNAL_SERVER_ERROR);
    }
}
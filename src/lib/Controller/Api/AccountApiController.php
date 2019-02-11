<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Helper\User\UserPasswordHelper;
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
     * @var UserPasswordHelper
     */
    protected $passwordHelper;

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
        UserPasswordHelper $passwordHelper,
        DeleteUserDataHelper $deleteUserDataHelper
    ) {
        parent::__construct($request);
        $this->userManager          = $userManager;
        $this->deleteUserDataHelper = $deleteUserDataHelper;
        $this->sessionService       = $sessionService;
        $this->environment          = $environment;
        $this->passwordHelper       = $passwordHelper;
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
     * @param string $password
     *
     * @return JSONResponse
     */
    public function setPassword(?string $password = null): JSONResponse {
        if($this->passwordHelper->setPassword($password)) {
            return $this->createJsonResponse(['success' => true], Http::STATUS_OK);
        }

        return $this->createJsonResponse(['success' => false], Http::STATUS_INTERNAL_SERVER_ERROR);
    }
}
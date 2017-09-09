<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:09
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Helper\PasswordApiObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\PasswordService;
use OCA\Passwords\Services\RevisionService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class PasswordApiController
 *
 * @package OCA\Passwords\Controller
 */
class PasswordApiController extends AbstractApiController {

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var RevisionService
     */
    protected $revisionService;
    /**
     * @var PasswordApiObjectHelper
     */
    private $passwordApiObjectHelper;

    /**
     * PasswordApiController constructor.
     *
     * @param string                  $appName
     * @param IRequest                $request
     * @param PasswordService         $passwordService
     * @param RevisionService         $revisionService
     * @param PasswordApiObjectHelper $passwordApiObjectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        PasswordService $passwordService,
        RevisionService $revisionService,
        PasswordApiObjectHelper $passwordApiObjectHelper
    ) {
        parent::__construct(
            $appName,
            $request,
            'PUT, POST, GET, DELETE, PATCH',
            'Authorization, Content-Type, Accept',
            1728000
        );
        $this->passwordService = $passwordService;
        $this->revisionService = $revisionService;
        $this->passwordApiObjectHelper = $passwordApiObjectHelper;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $level
     *
     * @return JSONResponse
     */
    public function list(string $level = 'default'): JSONResponse {

        $passwords = $this->passwordService->findPasswords();
        $results = [];

        foreach($passwords as $password) {
            $results[] =  $this->passwordApiObjectHelper->getPasswordInformation($password, $level);
        }

        return $this->createResponse(
            $results, 200
        );
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $login
     * @param string $password
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $title
     * @param string $url
     * @param string $notes
     * @param bool    $hidden
     * @param bool   $favourite
     * @param array  $folders
     * @param array  $tags
     *
     * @return JSONResponse
     */
    public function create(
        string $login,
        string $password,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        string $hash = '',
        string $title = '',
        string $url = '',
        string $notes = '',
        bool $hidden = false,
        bool $favourite = false,
        array $folders = [],
        array $tags = []
    ): JSONResponse {

        try {
            $revisionModel = $this->revisionService->createRevision(
                0, $login, $password, $cseType, $sseType, $hash, $title, $url, $notes, $hidden, $favourite
            );

            $passwordModel = $this->passwordService->createPassword();
            $passwordModel = $this->passwordService->savePassword($passwordModel);
            $revisionModel->setPasswordId($passwordModel->getId());
            $revisionModel = $this->revisionService->saveRevision($revisionModel);

            $this->passwordService->setPasswordRevision($passwordModel, $revisionModel);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }

        return $this->createResponse(
            ['password' => $passwordModel->getUuid(), 'revision' => $revisionModel->getUuid()], 201
        );
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param string $login
     * @param string $password
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $title
     * @param string $url
     * @param string $notes
     * @param bool   $hidden
     * @param bool   $favourite
     * @param array  $folders
     * @param array  $tags
     *
     * @return JSONResponse
     */
    public function update(
        string $id,
        string $login,
        string $password,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        string $hash = '',
        string $title = '',
        string $url = '',
        string $notes = '',
        bool $hidden = false,
        bool $favourite = false,
        array $folders = [],
        array $tags = []
    ): JSONResponse {

        try {
            $passwordModel = $this->passwordService->getPasswordByUuid($id);

            $revisionModel = $this->revisionService->createRevision(
                $passwordModel->getId(), $login, $password, $cseType, $sseType, $hash, $title, $url, $notes, $hidden, $favourite
            );
            $revisionModel = $this->revisionService->saveRevision($revisionModel);
            $this->passwordService->setPasswordRevision($passwordModel, $revisionModel);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }

        return $this->createResponse(
            ['password' => $passwordModel->getUuid(), 'revision' => $revisionModel->getUuid()], 200
        );
    }
}
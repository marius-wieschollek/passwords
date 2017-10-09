<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:09
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Helper\ApiObjects\PasswordObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use \OCA\Passwords\Services\Object\PasswordService;
use \OCA\Passwords\Services\Object\RevisionService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
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
     * @var PasswordObjectHelper
     */
    protected $passwordObjectHelper;

    /**
     * PasswordApiController constructor.
     *
     * @param string                                                $appName
     * @param IRequest                                              $request
     * @param PasswordService                                       $passwordService
     * @param RevisionService                                       $revisionService
     * @param PasswordObjectHelper $passwordObjectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        PasswordService $passwordService,
        RevisionService $revisionService,
        PasswordObjectHelper $passwordObjectHelper
    ) {
        parent::__construct(
            $appName,
            $request,
            'PUT, POST, GET, DELETE, PATCH',
            'Authorization, Content-Type, Accept',
            1728000
        );
        $this->passwordService      = $passwordService;
        $this->revisionService      = $revisionService;
        $this->passwordObjectHelper = $passwordObjectHelper;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $details
     *
     * @return JSONResponse
     */
    public function list(string $details = 'default'): JSONResponse {

        try {
            $passwords = $this->passwordService->findPasswords();
            $results   = [];

            foreach ($passwords as $password) {
                $object = $this->passwordObjectHelper->getApiObject($password, $details);

                if(!$object['hidden'] && !$object['trashed']) $results[] = $object;
            }

            ksort($results);

            return $this->createJsonResponse($results);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $details
     * @param array  $criteria
     *
     * @return JSONResponse
     */
    public function find(string $details = 'default', $criteria = []): JSONResponse {

        try {
            $passwords = $this->passwordService->findPasswords();
            $results   = [];

            foreach ($passwords as $password) {
                $object = $this->passwordObjectHelper->getApiObject($password, $details);

                foreach ($criteria as $key => $value) {
                    if($value == 'true') {
                        $value = true;
                    } else if($value == 'false') $value = false;

                    if($object[ $key ] != $value) continue 2;
                }

                if(!$object['hidden']) $results[] = $object;
            }

            ksort($results);

            return $this->createJsonResponse($results);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param string $details
     *
     * @return JSONResponse
     */
    public function show(string $id, string $details = 'default'): JSONResponse {

        try {
            $model    = $this->passwordService->getPasswordByUuid($id);
            $password = $this->passwordObjectHelper->getApiObject($model, $details);

            return $this->createJsonResponse($password);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
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
     * @param bool   $hidden
     * @param bool   $favourite
     * @param array  $folders
     * @param array  $tags
     *
     * @return JSONResponse
     */
    public function create(
        string $password,
        string $login,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        string $hash = '',
        string $title = '',
        string $url = '',
        string $notes = '',
        bool $hidden = false,
        bool $favourite = false,
        array $folders = ['00000000-0000-0000-0000-000000000000'],
        array $tags = []
    ): JSONResponse {

        try {
            $passwordModel = $this->passwordService->createPassword();
            $revisionModel = $this->revisionService->createRevision(
                $passwordModel->getUuid(), $password, $login, $cseType, $sseType, $hash, $title, $url, $notes, $hidden,
                false, false, $favourite
            );

            $passwordModel->setRevision($revisionModel->getUuid());
            $passwordModel = $this->passwordService->savePassword($passwordModel);
            $revisionModel = $this->revisionService->saveRevision($revisionModel);

            $this->passwordService->setPasswordFolderRelations($passwordModel->getUuid(), $folders);

            return $this->createJsonResponse(
                ['password' => $passwordModel->getUuid(), 'revision' => $revisionModel->getUuid()],
                Http::STATUS_CREATED
            );
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
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
        string $password,
        string $login,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        string $hash = '',
        string $title = '',
        string $url = '',
        string $notes = '',
        bool $hidden = false,
        bool $favourite = false,
        array $folders = ['00000000-0000-0000-0000-000000000000'],
        array $tags = []
    ): JSONResponse {

        try {
            $passwordModel = $this->passwordService->getPasswordByUuid($id);

            $revisionModel = $this->revisionService->createRevision(
                $passwordModel->getUuid(), $password, $login, $cseType, $sseType, $hash, $title, $url, $notes, $hidden,
                false, false, $favourite
            );

            $this->revisionService->saveRevision($revisionModel);
            $this->passwordService->setPasswordRevision($passwordModel, $revisionModel);
            $this->passwordService->setPasswordFolderRelations($passwordModel->getUuid(), $folders);

            return $this->createJsonResponse(
                ['password' => $passwordModel->getUuid(), 'revision' => $revisionModel->getUuid()]
            );
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return JSONResponse
     */
    public function delete(string $id): JSONResponse {
        try {
            $passwordModel = $this->passwordService->getPasswordByUuid($id);
            $oldRevision   = $this->revisionService->getCurrentRevision($passwordModel);
            $newRevision   = $this->revisionService->cloneRevision($oldRevision);

            if(!$newRevision->isTrashed()) {
                $newRevision->setTrashed(true);
            } else {
                $newRevision->setDeleted(true);
                $passwordModel->setDeleted(true);
                // @TODO Delete all revisions, remove from all folders and tags
            }

            $this->revisionService->saveRevision($newRevision);
            $this->passwordService->setPasswordRevision($passwordModel, $newRevision);

            return $this->createJsonResponse(
                ['password' => $passwordModel->getUuid(), 'revision' => $newRevision->getUuid()]
            );
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }
}
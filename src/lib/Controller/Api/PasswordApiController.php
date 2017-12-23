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
use OCA\Passwords\Services\Object\PasswordFolderRelationService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCP\AppFramework\Http;
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
     * @var PasswordRevisionService
     */
    protected $revisionService;

    /**
     * @var PasswordObjectHelper
     */
    protected $passwordObjectHelper;

    /**
     * PasswordApiController constructor.
     *
     * @param string                        $appName
     * @param IRequest                      $request
     * @param PasswordService               $passwordService
     * @param PasswordRevisionService       $revisionService
     * @param PasswordObjectHelper          $passwordObjectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        PasswordService $passwordService,
        PasswordRevisionService $revisionService,
        PasswordObjectHelper $passwordObjectHelper
    ) {
        parent::__construct(
            $appName,
            $request,
            'PUT, POST, GET, DELETE, PATCH',
            'Authorization, Content-Type, Accept',
            1728000
        );
        $this->passwordService       = $passwordService;
        $this->revisionService       = $revisionService;
        $this->passwordObjectHelper  = $passwordObjectHelper;
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
            $passwords = $this->passwordService->getAllPasswords();
            $results   = [];

            foreach ($passwords as $password) {
                if($password->isSuspended()) continue;
                $object = $this->passwordObjectHelper->getApiObject($password, $details);

                if(!$object['hidden'] && !$object['trashed']) $results[] = $object;
            }

            return $this->createJsonResponse($results);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param array  $criteria
     * @param string $details
     *
     * @return JSONResponse
     */
    public function find($criteria = [], string $details = 'default'): JSONResponse {

        try {
            $passwords = $this->passwordService->getAllPasswords();
            $results   = [];

            foreach ($passwords as $password) {
                if($password->isSuspended()) continue;
                $object = $this->passwordObjectHelper->getApiObject($password, $details);
                if($object['hidden']) continue;

                foreach ($criteria as $key => $value) {
                    if($value == 'true') {
                        $value = true;
                    } else if($value == 'false') $value = false;

                    if($object[ $key ] != $value) continue 2;
                }

                $results[] = $object;
            }

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
     * @param string $password
     * @param string $username
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $folder
     * @param bool   $hidden
     * @param bool   $favourite
     * @param array  $tags
     *
     * @TODO check folder access
     * @TODO check is system trash
     * @TODO check tag access
     *
     * @return JSONResponse
     * @internal param array $folders
     */
    public function create(
        string $password,
        string $username = '',
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        string $hash = '',
        string $label = '',
        string $url = '',
        string $notes = '',
        string $folder = '00000000-0000-0000-0000-000000000000',
        bool $hidden = false,
        bool $favourite = false,
        array $tags = []
    ): JSONResponse {

        try {
            $model = $this->passwordService->createPassword();
            $revision = $this->revisionService->createRevision(
                $model->getUuid(), $password, $username, $cseType, $sseType, $hash, $label, $url, $notes, $folder, $hidden,
                false, false, $favourite
            );

            $this->revisionService->saveRevision($revision);
            $this->passwordService->setPasswordRevision($model, $revision);

            return $this->createJsonResponse(
                ['password' => $model->getUuid(), 'revision' => $revision->getUuid()],
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
     * @param string $password
     * @param string $username
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $folder
     * @param bool   $hidden
     * @param bool   $favourite
     * @param array  $tags
     *
     * @TODO check folder access
     * @TODO check is system trash
     * @TODO check tag access
     *
     * @return JSONResponse
     */
    public function update(
        string $id,
        string $password,
        string $username = '',
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        string $hash = '',
        string $label = '',
        string $url = '',
        string $notes = '',
        string $folder = '00000000-0000-0000-0000-000000000000',
        bool $hidden = false,
        bool $favourite = false,
        array $tags = []
    ): JSONResponse {

        try {
            $model = $this->passwordService->getPasswordByUuid($id);

            $revision = $this->revisionService->createRevision(
                $model->getUuid(), $password, $username, $cseType, $sseType, $hash, $label, $url, $notes, $folder, $hidden,
                false, false, $favourite
            );

            $this->revisionService->saveRevision($revision);
            $this->passwordService->setPasswordRevision($model, $revision);

            return $this->createJsonResponse(['password' => $model->getUuid(), 'revision' => $revision->getUuid()]);
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
            $password    = $this->passwordService->getPasswordByUuid($id);
            $oldRevision = $this->revisionService->getCurrentRevision($password);

            if($oldRevision->isTrashed()) {
                $this->passwordService->deletePassword($password);

                return $this->createJsonResponse(['password' => $password->getUuid()]);
            }

            $newRevision = $this->revisionService->cloneRevision($oldRevision, ['trashed' => true]);
            $this->revisionService->saveRevision($newRevision);
            $this->passwordService->setPasswordRevision($password, $newRevision);

            return $this->createJsonResponse(['password' => $password->getUuid(), 'revision' => $newRevision->getUuid()]);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return JSONResponse
     */
    public function restore(string $id): JSONResponse {
        try {
            $password    = $this->passwordService->getPasswordByUuid($id);
            $oldRevision = $this->revisionService->getCurrentRevision($password);

            if($oldRevision->isTrashed()) {
                $newRevision = $this->revisionService->cloneRevision($oldRevision, ['trashed' => false]);
                $this->revisionService->saveRevision($newRevision);
                $this->passwordService->setPasswordRevision($password, $newRevision);

                return $this->createJsonResponse(['password' => $password->getUuid(), 'revision' => $newRevision->getUuid()]);
            }

            return $this->createJsonResponse(['password' => $password->getUuid(), 'revision' => $oldRevision->getUuid()]);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }
}
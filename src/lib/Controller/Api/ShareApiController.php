<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 18:32
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\ShareRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\ShareObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareRevisionService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Share\IManager;

/**
 * Class ShareApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class ShareApiController extends AbstractObjectApiController {

    /**
     * @var ShareService
     */
    protected $modelService;

    /**
     * @var ShareRevisionService
     */
    protected $revisionService;

    /**
     * @var ShareObjectHelper
     */
    protected $objectHelper;

    /**
     * @var PasswordService
     */
    private $passwordModelService;

    /**
     * @var PasswordRevisionService
     */
    private $passwordRevisionService;
    /**
     * @var IManager
     */
    private $shareManager;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'cseType', 'sseType', 'type'];

    /**
     * TagApiController constructor.
     *
     * @param string                  $appName
     * @param string             $userId
     * @param IRequest                $request
     * @param IManager                $shareManager
     * @param ShareService            $modelService
     * @param ShareRevisionService    $revisionService
     * @param PasswordService         $passwordModelService
     * @param PasswordRevisionService $passwordRevisionService
     * @param ShareObjectHelper       $objectHelper
     */
    public function __construct(
        string $appName,
        string $userId,
        IRequest $request,
        IManager $shareManager,
        ShareService $modelService,
        ShareRevisionService $revisionService,
        PasswordService $passwordModelService,
        PasswordRevisionService $passwordRevisionService,
        ShareObjectHelper $objectHelper
    ) {
        parent::__construct($appName, $request, $modelService, $revisionService, $objectHelper);

        $this->shareManager            = $shareManager;
        $this->passwordModelService    = $passwordModelService;
        $this->passwordRevisionService = $passwordRevisionService;
        $this->userId                  = $userId;
    }

    /**
     * @param string $password
     * @param string $type
     * @param string $with
     * @param bool   $editable
     * @param string $cseType
     *
     * @return JSONResponse
     */
    public function create(
        string $password,
        string $type,
        string $with,
        bool $editable = false,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION
    ): JSONResponse {

        try {
            $this->checkAccessPermissions();
            $passwordModel = $this->passwordModelService->findByUuid($password);
            /** @var PasswordRevision $passwordRevision */
            $passwordRevision = $this->passwordRevisionService->findByUuid($passwordModel->getRevision());

            if($passwordRevision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) {
                throw new ApiException('Resource has CSE activated', 420);
            }

            $share = $this->modelService->create(
                $passwordModel->getUuid(),
                $type,
                $with
            );

            $revision = $this->revisionService->create(
                $share->getUuid(),
                $passwordRevision->getPassword(),
                $passwordRevision->getUsername(),
                $passwordRevision->getUrl(),
                $passwordRevision->getLabel(),
                $passwordRevision->getNotes(),
                $passwordRevision->getHash(),
                $cseType,
                $editable
            );
            $revision->setSynchronized(false);

            $this->revisionService->save($revision);
            $this->modelService->setRevision($share, $revision);

            return $this->createJsonResponse(
                ['id' => $share->getUuid(), 'revision' => $revision->getUuid()], Http::STATUS_CREATED
            );
        } catch (\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }

    public function update(
        string $id,
        string $password,
        string $type,
        string $with,
        bool $editable = false,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION
    ): JSONResponse {

        try {
            $this->checkAccessPermissions();
            $folder = $this->modelService->findByUuid($id);

            return $this->createJsonResponse(['id' => $folder->getUuid(), 'revision' => $revision->getUuid()]);
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
            $this->checkAccessPermissions();
            $model       = $this->modelService->findByUuid($id);
            $this->modelService->delete($model);

            return $this->createJsonResponse(['id' => $model->getUuid()]);
        } catch (\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }

    /**
     * @param string $id
     * @param null   $revision
     *
     * @return JSONResponse
     */
    public function restore(string $id, $revision = null): JSONResponse {
        try {
            $this->checkAccessPermissions();
            $model = $this->modelService->findByUuid($id);

            if($revision === null) $revision = $model->getRevision();
            $oldRevision = $this->revisionService->findByUuid($revision);

            if($oldRevision->getModel() !== $model->getUuid()) {
                throw new ApiException('Invalid revision id', 400);
            }

            /** @var ShareRevision $newRevision */
            $newRevision = $this->revisionService->clone($oldRevision);
            $this->revisionService->save($newRevision);
            $this->modelService->setRevision($model, $newRevision);

            return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $newRevision->getUuid()]);
        } catch (\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }

    /**
     * @throws ApiException
     */
    protected function checkAccessPermissions(): void {
        $this->shareManager->shareApiEnabled();
        if($this->shareManager->sharingDisabledForUser($this->userId)) {
            throw new ApiException('Sharing disabled', 403);
        }

        parent::checkAccessPermissions();
    }
}
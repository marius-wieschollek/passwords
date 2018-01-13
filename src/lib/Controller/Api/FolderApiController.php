<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 13:44
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\FolderObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\ValidationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class FolderApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class FolderApiController extends AbstractObjectApiController {

    /**
     * @var FolderService
     */
    protected $modelService;

    /**
     * @var FolderObjectHelper
     */
    protected $objectHelper;

    /**
     * @var FolderRevisionService
     */
    protected $revisionService;

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'cseType', 'sseType', 'trashed', 'favourite'];

    /**
     * FolderApiController constructor.
     *
     * @param IRequest              $request
     * @param FolderService         $modelService
     * @param FolderObjectHelper    $objectHelper
     * @param ValidationService     $validationService
     * @param FolderRevisionService $revisionService
     */
    public function __construct(
        IRequest $request,
        FolderService $modelService,
        FolderObjectHelper $objectHelper,
        ValidationService $validationService,
        FolderRevisionService $revisionService
    ) {
        parent::__construct($request, $modelService, $objectHelper, $validationService, $revisionService);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $label
     * @param string $parent
     * @param string $cseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \Exception
     *
     */
    public function create(
        string $label,
        string $parent,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {
        $model    = $this->modelService->create();
        $revision = $this->revisionService->create(
            $model->getUuid(), $label, $parent, $cseType, $hidden, false, $favourite
        );

        $this->revisionService->save($revision);
        $this->modelService->setRevision($model, $revision);

        return $this->createJsonResponse(
            ['id' => $model->getUuid(), 'revision' => $revision->getUuid()],
            Http::STATUS_CREATED
        );
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param string $label
     * @param string $parent
     * @param string $cseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     *
     */
    public function update(
        string $id,
        string $label,
        string $parent,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {
        if($id === $this->modelService::BASE_FOLDER_UUID) throw new ApiException('Can not edit base folder', 422);

        $model = $this->modelService->findByUuid($id);
        /** @var FolderRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($model->getRevision());

        $revision = $this->revisionService->create(
            $model->getUuid(), $label, $parent, $cseType, $hidden, $oldRevision->isTrashed(), $favourite
        );

        $this->revisionService->save($revision);
        $this->modelService->setRevision($model, $revision);

        return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $revision->getUuid()]);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function delete(string $id): JSONResponse {
        if($id === $this->modelService::BASE_FOLDER_UUID) {
            throw new ApiException('Can not edit base folder', 422);
        }

        return parent::delete($id);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param null   $revision
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function restore(string $id, $revision = null): JSONResponse {
        if($id === $this->modelService::BASE_FOLDER_UUID || $revision == $this->revisionService::BASE_REVISION_UUID) {
            throw new ApiException('Can not edit base folder', 422);
        }

        return parent::restore($id, $revision);
    }
}
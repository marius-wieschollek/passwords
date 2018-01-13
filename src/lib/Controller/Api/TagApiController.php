<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 19:45
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Helper\ApiObjects\TagObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class TagApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class TagApiController extends AbstractObjectApiController {

    /**
     * @var TagService
     */
    protected $modelService;

    /**
     * @var TagRevisionService
     */
    protected $revisionService;

    /**
     * @var TagObjectHelper
     */
    protected $objectHelper;

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'cseType', 'sseType', 'trashed', 'favourite'];

    /**
     * TagApiController constructor.
     *
     * @param IRequest           $request
     * @param TagService         $modelService
     * @param TagObjectHelper    $objectHelper
     * @param TagRevisionService $revisionService
     */
    public function __construct(
        IRequest $request,
        TagService $modelService,
        TagObjectHelper $objectHelper,
        TagRevisionService $revisionService
    ) {
        parent::__construct($request, $modelService, $objectHelper, $revisionService);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $label
     * @param string $color
     * @param string $cseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     */
    public function create(
        string $label,
        string $color,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {
        try {
            $this->checkAccessPermissions();
            $model    = $this->modelService->create();
            $revision = $this->revisionService->create(
                $model->getUuid(), $label, $color, $cseType, $hidden, false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($model, $revision);

            return $this->createJsonResponse(
                ['id' => $model->getUuid(), 'revision' => $revision->getUuid()],
                Http::STATUS_CREATED
            );
        } catch(\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param string $label
     * @param string $color
     * @param string $cseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     *
     */
    public function update(
        string $id,
        string $label,
        string $color,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {
        try {
            $this->checkAccessPermissions();
            $model = $this->modelService->findByUuid($id);

            /** @var TagRevision $oldRevision */
            $oldRevision = $this->revisionService->findByUuid($model->getRevision());
            $revision    = $this->revisionService->create(
                $model->getUuid(), $label, $color, $cseType, $hidden, $oldRevision->isTrashed(), $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($model, $revision);

            return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $revision->getUuid()]);
        } catch(\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }
}
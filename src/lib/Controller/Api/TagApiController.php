<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Helper\ApiObjects\TagObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCA\Passwords\Services\ValidationService;
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
     * @var TagObjectHelper
     */
    protected $objectHelper;

    /**
     * @var TagRevisionService
     */
    protected $revisionService;

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'edited', 'cseType', 'sseType', 'trashed', 'favorite'];

    /**
     * TagApiController constructor.
     *
     * @param IRequest           $request
     * @param TagService         $modelService
     * @param TagObjectHelper    $objectHelper
     * @param TagRevisionService $revisionService
     * @param ValidationService  $validationService
     */
    public function __construct(
        IRequest $request,
        TagService $modelService,
        TagObjectHelper $objectHelper,
        TagRevisionService $revisionService,
        ValidationService $validationService
    ) {
        parent::__construct($request, $modelService, $objectHelper, $validationService, $revisionService);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $label
     * @param string $color
     * @param string $cseKey
     * @param string $cseType
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $favorite
     *
     * @return JSONResponse
     * @throws \Exception
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function create(
        string $label,
        string $color,
        string $cseKey = '',
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        int $edited = 0,
        bool $hidden = false,
        bool $favorite = false
    ): JSONResponse {
        if($edited < 1) $edited = time();

        $model    = $this->modelService->create();
        $revision = $this->revisionService->create(
            $model->getUuid(), $label, $color, $cseKey, $cseType, $edited, $hidden, false, $favorite
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
     * @param string $color
     * @param string $cseKey
     * @param string $cseType
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $favorite
     *
     * @return JSONResponse
     * @throws \Exception
     * @throws \OCA\Passwords\Exception\ApiException
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function update(
        string $id,
        string $label,
        string $color,
        string $cseKey,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        int $edited = 0,
        bool $hidden = false,
        bool $favorite = false
    ): JSONResponse {
        $model = $this->modelService->findByUuid($id);

        /** @var TagRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($model->getRevision());
        if($edited < 1) $edited = $oldRevision->getEdited();
        $revision = $this->revisionService->create(
            $model->getUuid(), $label, $color, $cseKey, $cseType, $edited, $hidden, $oldRevision->isTrashed(), $favorite
        );

        $this->revisionService->save($revision);
        $this->modelService->setRevision($model, $revision);

        return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $revision->getUuid()]);
    }
}
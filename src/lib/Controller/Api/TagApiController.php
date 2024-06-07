<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Controller\Api;

use Exception;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\AbstractObjectHelper;
use OCA\Passwords\Helper\ApiObjects\TagObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\AbstractModelService;
use OCA\Passwords\Services\Object\AbstractRevisionService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCA\Passwords\Services\ValidationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class TagApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class TagApiController extends AbstractObjectApiController {

    /**
     * @var TagService|AbstractModelService
     */
    protected AbstractModelService $modelService;

    /**
     * @var TagObjectHelper|AbstractObjectHelper
     */
    protected AbstractObjectHelper $objectHelper;

    /**
     * @var TagRevisionService|AbstractRevisionService
     */
    protected AbstractRevisionService $revisionService;

    /**
     * @var array
     */
    protected array $allowedFilterFields = ['created', 'updated', 'edited', 'cseType', 'sseType', 'trashed', 'favorite'];

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
     * @param string $label
     * @param string $color
     * @param string $cseKey
     * @param string $cseType
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $favorite
     *
     * @return JSONResponse
     * @throws Exception
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function create(
        string $label = '',
        string $color = '',
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
     * @param string      $id
     * @param string      $label
     * @param string      $color
     * @param string|null $revision
     * @param string      $cseKey
     * @param string      $cseType
     * @param int         $edited
     * @param bool        $hidden
     * @param bool        $favorite
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function update(
        string $id,
        string $label,
        string $color,
        string $revision = null,
        string $cseKey = '',
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        int $edited = 0,
        bool $hidden = false,
        bool $favorite = false
    ): JSONResponse {
        $model = $this->modelService->findByUuid($id);
        if($revision !== null && $revision !== $model->getRevision()) {
            throw new ApiException('Outdated revision id', Http::STATUS_CONFLICT);
        }

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
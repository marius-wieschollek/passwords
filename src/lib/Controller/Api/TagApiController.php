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
    protected $allowedFilterFields = ['created', 'updated', 'edited', 'cseType', 'sseType', 'trashed', 'favourite'];

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
     * @param string $cseType
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     * @throws \Exception
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function create(
        string $label,
        string $color,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        int $edited = 0,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {
        if($edited === 0) $edited = time();

        $model    = $this->modelService->create();
        $revision = $this->revisionService->create(
            $model->getUuid(), $label, $color, $cseType, $edited, $hidden, false, $favourite
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
     * @param string $cseType
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $favourite
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
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        int $edited = 0,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {
        $model = $this->modelService->findByUuid($id);

        /** @var TagRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($model->getRevision());
        if($edited === 0) $edited = $oldRevision->getEdited();
        $revision = $this->revisionService->create(
            $model->getUuid(), $label, $color, $cseType, $edited, $hidden, $oldRevision->isTrashed(), $favourite
        );

        $this->revisionService->save($revision);
        $this->modelService->setRevision($model, $revision);

        return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $revision->getUuid()]);
    }
}
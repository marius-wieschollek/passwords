<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 13:44
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Helper\ApiObjects\FolderObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
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
     * PasswordApiController constructor.
     *
     * @param string                $appName
     * @param IRequest              $request
     * @param FolderService         $modelService
     * @param FolderRevisionService $revisionService
     * @param FolderObjectHelper    $objectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        FolderService $modelService,
        FolderRevisionService $revisionService,
        FolderObjectHelper $objectHelper
    ) {
        parent::__construct($appName, $request, $modelService, $revisionService, $objectHelper);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $label
     * @param string $parent
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @TODO check $parent access
     * @TODO check is system trash
     *
     * @return JSONResponse
     */
    public function create(
        string $label,
        string $parent,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {

        try {
            $folder   = $this->modelService->create();
            $revision = $this->revisionService->create(
                $folder->getUuid(), $label, $parent, $cseType, $sseType, $hidden, false, false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($folder, $revision);

            return $this->createJsonResponse(
                ['id' => $folder->getUuid(), 'revision' => $revision->getUuid()],
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
     * @param string $label
     * @param string $parent
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @TODO check $parent access
     * @TODO check is system trash
     *
     * @return JSONResponse
     */
    public function update(
        string $id,
        string $label,
        string $parent,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {

        try {
            $folder = $this->modelService->findByUuid($id);

            $revision = $this->revisionService->create(
                $folder->getUuid(), $label, $parent, $cseType, $sseType, $hidden, false, false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($folder, $revision);

            return $this->createJsonResponse(['id' => $folder->getUuid(), 'revision' => $revision->getUuid()]);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }
}
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
class FolderApiController extends AbstractApiController {

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * @var FolderObjectHelper
     */
    protected $folderObjectHelper;

    /**
     * @var FolderRevisionService
     */
    protected $revisionService;

    /**
     * PasswordApiController constructor.
     *
     * @param string                        $appName
     * @param IRequest                      $request
     * @param FolderService                 $folderService
     * @param FolderRevisionService         $revisionService
     * @param FolderObjectHelper            $folderObjectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        FolderService $folderService,
        FolderRevisionService $revisionService,
        FolderObjectHelper $folderObjectHelper
    ) {
        parent::__construct(
            $appName,
            $request,
            'PUT, POST, GET, DELETE, PATCH',
            'Authorization, Content-Type, Accept',
            1728000
        );
        $this->folderService      = $folderService;
        $this->folderObjectHelper = $folderObjectHelper;
        $this->revisionService    = $revisionService;
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
            $folders = $this->folderService->getAllFolders();
            $results = [];

            foreach ($folders as $folder) {
                if($folder->isSuspended()) continue;
                $object = $this->folderObjectHelper->getApiObject($folder, $details);

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
     * @param string $details
     * @param array  $criteria
     *
     * @return JSONResponse
     */
    public function find(string $details = 'default', $criteria = []): JSONResponse {
        try {
            $folders = $this->folderService->getAllFolders();
            $results = [];

            foreach ($folders as $folder) {
                if($folder->isSuspended()) continue;
                $object = $this->folderObjectHelper->getApiObject($folder, $details);
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
            $model  = $this->folderService->getFolderByUuid($id);
            $folder = $this->folderObjectHelper->getApiObject($model, $details);

            return $this->createJsonResponse($folder);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
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
            $folder   = $this->folderService->createFolder();
            $revision = $this->revisionService->createRevision(
                $folder->getUuid(), $label, $parent, $cseType, $sseType, $hidden, false, false, $favourite
            );

            $this->revisionService->saveRevision($revision);
            $this->folderService->setFolderRevision($folder, $revision);

            return $this->createJsonResponse(
                ['folder' => $folder->getUuid(), 'revision' => $revision->getUuid()],
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
            $folder = $this->folderService->getFolderByUuid($id);

            $revision = $this->revisionService->createRevision(
                $folder->getUuid(), $label, $parent, $cseType, $sseType, $hidden, false, false, $favourite
            );

            $this->revisionService->saveRevision($revision);
            $this->folderService->setFolderRevision($folder, $revision);

            return $this->createJsonResponse(['folder' => $folder->getUuid(), 'revision' => $revision->getUuid()]);
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
            $folder      = $this->folderService->getFolderByUuid($id);
            $oldRevision = $this->revisionService->getCurrentRevision($folder);

            if($oldRevision->isTrashed()) {
                $this->folderService->deleteFolder($folder);
                return $this->createJsonResponse(['folder' => $folder->getUuid()]);
            }

            $newRevision = $this->revisionService->cloneRevision($oldRevision, ['trashed' => true]);
            $this->revisionService->saveRevision($newRevision);
            $this->folderService->setFolderRevision($folder, $newRevision);

            return $this->createJsonResponse(['folder' => $folder->getUuid(), 'revision' => $newRevision->getUuid()]);
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

            $folder      = $this->folderService->getFolderByUuid($id);
            $oldRevision = $this->revisionService->getCurrentRevision($folder);

            if($oldRevision->isTrashed()) {
                $newRevision = $this->revisionService->cloneRevision($oldRevision, ['trashed' => false]);
                $this->revisionService->saveRevision($newRevision);
                $this->folderService->setFolderRevision($folder, $newRevision);

                return $this->createJsonResponse(['folder' => $folder->getUuid(), 'revision' => $newRevision->getUuid()]);
            }

            return $this->createJsonResponse(['folder' => $folder->getUuid(), 'revision' => $oldRevision->getUuid()]);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }
}
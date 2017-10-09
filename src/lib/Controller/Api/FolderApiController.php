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
use \OCA\Passwords\Services\Object\FolderService;
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
     * PasswordApiController constructor.
     *
     * @param string             $appName
     * @param IRequest           $request
     * @param FolderService      $folderService
     * @param FolderObjectHelper $folderObjectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        FolderService $folderService,
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
            $folders = $this->folderService->findFolders();
            $results = [];

            foreach ($folders as $folder) {
                if(!$folder->isHidden() && !$folder->isTrashed()) continue;

                $results[] = $this->folderObjectHelper->getApiObject($folder, $details);
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
            $folders = $this->folderService->findFolders();
            $results = [];

            foreach ($folders as $folder) {
                if(!$folder->isHidden()) continue;
                $object = $this->folderObjectHelper->getApiObject($folder, $details);

                foreach ($criteria as $key => $value) {
                    if($value == 'true') {
                        $value = true;
                    } else if($value == 'false') $value = false;

                    if($object[ $key ] != $value) continue 2;
                }

                $results[] = $object;
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
     * @param string $name
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     */
    public function create(
        string $name,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {

        try {
            $model = $this->folderService->createFolder($name, $cseType, $sseType, $hidden, false, false, $favourite);
            $model = $this->folderService->saveFolder($model);

            return $this->createJsonResponse(['folder' => $model->getUuid()], Http::STATUS_CREATED);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param string $name
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     */
    public function update(
        string $id,
        string $name,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {

        try {
            $model = $this->folderService->getFolderByUuid($id);
            $model->setName($name);
            $model->setCseType($cseType);
            $model->setSseType($sseType);
            $model->setHidden($hidden);
            $model->setFavourite($favourite);
            $model = $this->folderService->saveFolder($model);

            return $this->createJsonResponse(['folder' => $model->getUuid()]);
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
            $model = $this->folderService->getFolderByUuid($id);

            if(!$model->isTrashed()) {
                $model->setTrashed(true);
            } else {
                $model->setDeleted(true);
                // @TODO Delete all passwords, remove from all folders
            }

            $this->folderService->saveFolder($model);

            return $this->createJsonResponse(['folder' => $model->getUuid()]);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }
}
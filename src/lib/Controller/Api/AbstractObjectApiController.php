<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 24.12.17
 * Time: 16:24
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\AbstractRevisionEntity;
use OCA\Passwords\Helper\ApiObjects\AbstractObjectHelper;
use OCA\Passwords\Services\Object\AbstractModelService;
use OCA\Passwords\Services\Object\AbstractRevisionService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class AbstractObjectApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
abstract class AbstractObjectApiController extends AbstractApiController {

    /**
     * @var AbstractModelService
     */
    protected $modelService;

    /**
     * @var AbstractObjectHelper
     */
    protected $objectHelper;

    /**
     * @var AbstractRevisionService
     */
    protected $revisionService;

    /**
     * PasswordApiController constructor.
     *
     * @param string                  $appName
     * @param IRequest                $request
     * @param AbstractModelService    $modelService
     * @param AbstractRevisionService $revisionService
     * @param AbstractObjectHelper    $objectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        AbstractModelService $modelService,
        AbstractRevisionService $revisionService,
        AbstractObjectHelper $objectHelper
    ) {
        parent::__construct(
            $appName,
            $request,
            'PUT, POST, GET, DELETE, PATCH',
            'Authorization, Content-Type, Accept',
            1728000
        );
        $this->modelService    = $modelService;
        $this->objectHelper    = $objectHelper;
        $this->revisionService = $revisionService;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $details
     *
     * @return JSONResponse
     */
    public function list(string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        try {
            $folders = $this->modelService->findAll();
            $results = [];

            foreach ($folders as $folder) {
                if($folder->isSuspended()) continue;
                $object = $this->objectHelper->getApiObject($folder, $details, true, true);

                if($object !== null) $results[] = $object;
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
    public function find($criteria = [], string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        try {
            $folders = $this->modelService->findAll();
            $results = [];

            foreach ($folders as $folder) {
                if($folder->isSuspended()) continue;
                $object = $this->objectHelper->getApiObject($folder, $details);
                if($object === null) continue;

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
    public function show(string $id, string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        try {
            $model  = $this->modelService->findByUuid($id);
            $folder = $this->objectHelper->getApiObject($model, $details, false);

            return $this->createJsonResponse($folder);
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
            $folder      = $this->modelService->findByUuid($id);
            $oldRevision = $this->revisionService->findByUuid($folder->getRevision());

            if($oldRevision->isTrashed()) {
                $this->modelService->delete($folder);

                return $this->createJsonResponse(['folder' => $folder->getUuid()]);
            }

            /** @var AbstractRevisionEntity $newRevision */
            $newRevision = $this->revisionService->clone($oldRevision, ['trashed' => true]);
            $this->revisionService->save($newRevision);
            $this->modelService->setRevision($folder, $newRevision);

            return $this->createJsonResponse(['id' => $folder->getUuid(), 'revision' => $newRevision->getUuid()]);
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

            $folder      = $this->modelService->findByUuid($id);
            $oldRevision = $this->revisionService->findByUuid($folder->getRevision());

            if($oldRevision->isTrashed()) {
                /** @var AbstractRevisionEntity $newRevision */
                $newRevision = $this->revisionService->clone($oldRevision, ['trashed' => false]);
                $this->revisionService->save($newRevision);
                $this->modelService->setRevision($folder, $newRevision);

                return $this->createJsonResponse(['id' => $folder->getUuid(), 'revision' => $newRevision->getUuid()]);
            }

            return $this->createJsonResponse(['id' => $folder->getUuid(), 'revision' => $oldRevision->getUuid()]);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 24.12.17
 * Time: 16:24
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\AbstractRevisionEntity;
use OCA\Passwords\Exception\ApiException;
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
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'cseType', 'sseType'];

    /**
     * PasswordApiController constructor.
     *
     * @param IRequest                $request
     * @param AbstractModelService    $modelService
     * @param AbstractRevisionService $revisionService
     * @param AbstractObjectHelper    $objectHelper
     */
    public function __construct(
        IRequest $request,
        AbstractModelService $modelService,
        AbstractRevisionService $revisionService,
        AbstractObjectHelper $objectHelper
    ) {
        parent::__construct($request);
        $this->modelService    = $modelService;
        $this->objectHelper    = $objectHelper;
        $this->revisionService = $revisionService;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $details
     *
     * @return JSONResponse
     */
    public function list(string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        try {
            $this->checkAccessPermissions();
            /** @var AbstractModelEntity[] $models */
            $models  = $this->modelService->findAll();
            $results = [];

            foreach($models as $model) {
                if($model->isSuspended()) continue;
                $object = $this->objectHelper->getApiObject($model, $details, ['hidden' => false, 'trashed' => false]);

                if($object !== null) $results[] = $object;
            }

            return $this->createJsonResponse($results);
        } catch(\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }

    /**
     * @CORS
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
            $this->checkAccessPermissions();
            $filters           = $this->processSearchCriteria($criteria);
            $filters['hidden'] = false;
            /** @var AbstractModelEntity[] $models */
            $models  = $this->modelService->findAll();
            $results = [];

            foreach($models as $model) {
                if($model->isSuspended()) continue;
                $object = $this->objectHelper->getApiObject($model, $details, $filters);
                if($object === null) continue;
                $results[] = $object;
            }

            return $this->createJsonResponse($results);
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
     * @param string $details
     *
     * @return JSONResponse
     */
    public function show(string $id, string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        try {
            $this->checkAccessPermissions();
            $model  = $this->modelService->findByUuid($id);
            $object = $this->objectHelper->getApiObject($model, $details);

            return $this->createJsonResponse($object);
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
     *
     * @return JSONResponse
     */
    public function delete(string $id): JSONResponse {
        try {
            $this->checkAccessPermissions();
            $model = $this->modelService->findByUuid($id);
            /** @var AbstractRevisionEntity $oldRevision */
            $oldRevision = $this->revisionService->findByUuid($model->getRevision());

            if($oldRevision->isTrashed()) {
                $this->modelService->delete($model);

                return $this->createJsonResponse(['id' => $model->getUuid()]);
            }

            /** @var AbstractRevisionEntity $newRevision */
            $newRevision = $this->revisionService->clone($oldRevision, ['trashed' => true]);
            $this->revisionService->save($newRevision);
            $this->modelService->setRevision($model, $newRevision);

            return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $newRevision->getUuid()]);
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
     * @param null   $revision
     *
     * @return JSONResponse
     */
    public function restore(string $id, $revision = null): JSONResponse {
        try {
            $this->checkAccessPermissions();
            $model = $this->modelService->findByUuid($id);

            if($revision === null) $revision = $model->getRevision();
            /** @var AbstractRevisionEntity $oldRevision */
            $oldRevision = $this->revisionService->findByUuid($revision, true);

            if($oldRevision->getModel() !== $model->getUuid()) {
                throw new ApiException('Invalid revision id', 400);
            }

            if(!$oldRevision->isTrashed() && $oldRevision->getUuid() === $model->getRevision()) {
                return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $oldRevision->getUuid()]);
            }

            /** @var AbstractRevisionEntity $newRevision */
            $newRevision = $this->revisionService->clone($oldRevision, ['trashed' => false]);
            $this->revisionService->save($newRevision);
            $this->modelService->setRevision($model, $newRevision);

            return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $newRevision->getUuid()]);
        } catch(\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }
}
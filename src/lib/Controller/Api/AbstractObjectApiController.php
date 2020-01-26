<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\AbstractModel;
use OCA\Passwords\Db\AbstractRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\AbstractObjectHelper;
use OCA\Passwords\Services\Object\AbstractModelService;
use OCA\Passwords\Services\Object\AbstractRevisionService;
use OCA\Passwords\Services\ValidationService;
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
     * @var ValidationService
     */
    protected $validationService;

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'cseType', 'sseType'];

    /**
     * AbstractObjectApiController constructor.
     *
     * @param IRequest                $request
     * @param AbstractModelService    $modelService
     * @param AbstractObjectHelper    $objectHelper
     * @param ValidationService       $validationService
     * @param AbstractRevisionService $revisionService
     */
    public function __construct(
        IRequest $request,
        AbstractModelService $modelService,
        AbstractObjectHelper $objectHelper,
        ValidationService $validationService,
        AbstractRevisionService $revisionService
    ) {
        parent::__construct($request);
        $this->modelService      = $modelService;
        $this->objectHelper      = $objectHelper;
        $this->revisionService   = $revisionService;
        $this->validationService = $validationService;
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
        /** @var AbstractModel[] $models */
        $models  = $this->modelService->findAll();
        $results = [];

        foreach($models as $model) {
            if($model->isSuspended()) continue;
            $object = $this->objectHelper->getApiObject($model, $details, ['hidden' => false, 'trashed' => false]);

            if($object !== null) $results[] = $object;
        }

        return $this->createJsonResponse($results);
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
     * @throws ApiException
     */
    public function find($criteria = [], string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        $filters = $this->processSearchCriteria($criteria);
        if(!isset($filters['trashed'])) $filters['trashed'] = false;
        $filters['hidden'] = false;
        /** @var AbstractModel[] $models */
        $models  = $this->modelService->findAll();
        $results = [];

        foreach($models as $model) {
            if($model->isSuspended()) continue;
            $object = $this->objectHelper->getApiObject($model, $details, $filters);
            if($object === null) continue;
            $results[] = $object;
        }

        return $this->createJsonResponse($results);
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
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function show(string $id, string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        $model  = $this->modelService->findByUuid($id);
        $object = $this->objectHelper->getApiObject($model, $details);

        return $this->createJsonResponse($object);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string      $id
     * @param null|string $revision
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function delete(string $id, ?string $revision = null): JSONResponse {
        $model = $this->modelService->findByUuid($id);
        /** @var AbstractRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($model->getRevision());

        if($oldRevision->isTrashed()) {
            if($revision !== null && $revision !== $model->getRevision()){
                throw new ApiException('Outdated revision id', 400);
            }

            $this->modelService->delete($model);

            return $this->createJsonResponse(['id' => $model->getUuid()]);
        }

        /** @var AbstractRevision $newRevision */
        $newRevision = $this->revisionService->clone($oldRevision, ['trashed' => true]);
        $this->revisionService->save($newRevision);
        $this->modelService->setRevision($model, $newRevision);

        return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $newRevision->getUuid()]);
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
        $model = $this->modelService->findByUuid($id);

        if($revision === null) $revision = $model->getRevision();
        /** @var AbstractRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($revision, true);

        if($oldRevision->getModel() !== $model->getUuid()) {
            throw new ApiException('Invalid revision id', 400);
        }

        if(!$oldRevision->isTrashed() && $oldRevision->getUuid() === $model->getRevision()) {
            return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $oldRevision->getUuid()]);
        }

        /** @var AbstractRevision $newRevision */
        $newRevision = $this->revisionService->clone($oldRevision, ['trashed' => false]);
        $newRevision = $this->validationService->validateObject($newRevision);
        $this->revisionService->save($newRevision);
        $this->modelService->setRevision($model, $newRevision);

        return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $newRevision->getUuid()]);
    }
}
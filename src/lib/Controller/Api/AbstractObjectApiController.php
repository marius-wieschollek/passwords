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
use OCA\Passwords\Db\AbstractModel;
use OCA\Passwords\Db\AbstractRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\AbstractObjectHelper;
use OCA\Passwords\Services\Object\AbstractModelService;
use OCA\Passwords\Services\Object\AbstractRevisionService;
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
 * Class AbstractObjectApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
abstract class AbstractObjectApiController extends AbstractApiController {

    /**
     * @var AbstractModelService
     */
    protected AbstractModelService $modelService;

    /**
     * @var AbstractObjectHelper
     */
    protected AbstractObjectHelper $objectHelper;

    /**
     * @var AbstractRevisionService
     */
    protected AbstractRevisionService $revisionService;

    /**
     * @var ValidationService
     */
    protected ValidationService $validationService;

    /**
     * @var array
     */
    protected array $allowedFilterFields = ['created', 'updated', 'cseType', 'sseType'];

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
     * @param string $details
     *
     * @return JSONResponse
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
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
     * @param array  $criteria
     * @param string $details
     *
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
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
     * @param string $id
     * @param string $details
     *
     * @return JSONResponse
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function show(string $id, string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        $model  = $this->modelService->findByUuid($id);
        $object = $this->objectHelper->getApiObject($model, $details);

        return $this->createJsonResponse($object);
    }

    /**
     * @param string      $id
     * @param null|string $revision
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function delete(string $id, ?string $revision = null): JSONResponse {
        $model = $this->modelService->findByUuid($id);
        /** @var AbstractRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($model->getRevision());

        if($oldRevision->isTrashed()) {
            if($revision !== null && $revision !== $model->getRevision()){
                throw new ApiException('Outdated revision id', Http::STATUS_BAD_REQUEST);
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
     * @param string $id
     * @param null   $revision
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function restore(string $id, $revision = null): JSONResponse {
        $model = $this->modelService->findByUuid($id);

        if($revision === null) $revision = $model->getRevision();
        /** @var AbstractRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($revision, true);

        if($oldRevision->getModel() !== $model->getUuid()) {
            throw new ApiException('Invalid revision id', Http::STATUS_BAD_REQUEST);
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
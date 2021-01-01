<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api\Legacy;

use Exception;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class LegacyCategoryApiController
 *
 * @package Controller\Api\Legacy
 */
class LegacyCategoryApiController extends ApiController {

    /**
     * @var TagService
     */
    protected TagService $tagService;

    /**
     * @var LoggingService
     */
    protected LoggingService $loggingService;

    /**
     * @var TagRevisionService
     */
    protected TagRevisionService $tagRevisionService;

    /**
     * LegacyCategoryApiController constructor.
     *
     * @param IRequest           $request
     * @param TagService         $tagService
     * @param LoggingService     $loggingService
     * @param TagRevisionService $tagRevisionService
     */
    public function __construct(
        IRequest $request,
        TagService $tagService,
        LoggingService $loggingService,
        TagRevisionService $tagRevisionService
    ) {
        parent::__construct(
            Application::APP_NAME,
            $request,
            'GET, POST, DELETE, PUT, PATCH',
            'Authorization, Content-Type, Accept',
            1728000
        );
        $this->tagService         = $tagService;
        $this->loggingService     = $loggingService;
        $this->tagRevisionService = $tagRevisionService;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function index(): JSONResponse {
        $categories = [];
        /** @var Tag[] $models */
        $models = $this->tagService->findAll();
        foreach($models as $model) {
            if($model->isSuspended()) continue;
            try {
                $category = $this->getCategoryObject($model);
            } catch(Exception $e) {
                $this->loggingService->logException($e);

                continue;
            }
            if($category !== null) $categories[] = $category;
        }

        return new JSONResponse($categories);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param $id
     *
     * @return mixed
     * @throws Exception
     */
    public function show($id): JSONResponse {
        /** @var Tag $tag */
        $tag = $this->tagService->findByIdOrUuid($id);
        if($tag === null) return new JSONResponse('Entity not found', 404);
        $category = $this->getCategoryObject($tag);
        if($category === null) return new JSONResponse('Entity not found', 404);

        return new JSONResponse($category);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param $categoryName
     * @param $categoryColour
     *
     * @return JSONResponse
     * @throws Exception
     * @throws ApiException
     */
    public function create($categoryName, $categoryColour): JSONResponse {
        /** @var Tag $model */
        $model    = $this->tagService->create();
        $revision = $this->tagRevisionService->create(
            $model->getUuid(), strval($categoryName), '#'.strval($categoryColour), '', EncryptionService::CSE_ENCRYPTION_NONE, time(), false, false, false
        );

        $this->tagRevisionService->save($revision);
        $this->tagService->setRevision($model, $revision);

        return new JSONResponse($this->getCategoryObject($model));
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param $id
     *
     * @return JSONResponse
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function destroy($id): JSONResponse {
        /** @var Tag $tag */
        $tag = $this->tagService->findByIdOrUuid($id);
        if($tag === null) return new JSONResponse('Entity not found', 404);
        /** @var TagRevision $oldRevision */
        $oldRevision = $this->tagRevisionService->findByUuid($tag->getRevision());

        /** @var TagRevision $newRevision */
        $newRevision = $this->tagRevisionService->clone($oldRevision, ['trashed' => true]);
        $this->tagRevisionService->save($newRevision);
        $this->tagService->setRevision($tag, $newRevision);

        return new JSONResponse(['id' => $tag->getId()]);
    }

    /**
     * @param Tag $tag
     *
     * @return array|null
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    protected function getCategoryObject(Tag $tag): ?array {
        /** @var TagRevision $revision */
        $revision = $this->tagRevisionService->findByUuid($tag->getRevision(), true);

        if($revision->isHidden() || $revision->isTrashed()) {
            return null;
        }
        if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) {
            return null;
        }
        if(!in_array($revision->getSseType(), [EncryptionService::SSE_ENCRYPTION_V1R1, EncryptionService::SSE_ENCRYPTION_V1R2])) {
            return null;
        }

        return [
            'id'              => $tag->getId(),
            'user_id'         => $tag->getUserId(),
            'category_name'   => $revision->getLabel(),
            'category_colour' => substr($revision->getColor(), 1)
        ];
    }
}
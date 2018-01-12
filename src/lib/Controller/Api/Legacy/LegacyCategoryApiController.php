<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.01.18
 * Time: 12:03
 */

namespace OCA\Passwords\Controller\Api\Legacy;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\ApiController;
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
    protected $tagService;

    /**
     * @var TagRevisionService
     */
    protected $tagRevisionService;

    /**
     * LegacyCategoryApiController constructor.
     *
     * @param IRequest           $request
     * @param TagService         $tagService
     * @param TagRevisionService $tagRevisionService
     */
    public function __construct(
        IRequest $request,
        TagService $tagService,
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
        $this->tagRevisionService = $tagRevisionService;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function index(): JSONResponse {
        $counter    = 0;
        $categories = new \stdClass();
        /** @var Tag[] $models */
        $models = $this->tagService->findAll();
        foreach($models as $model) {
            if($model->isSuspended()) continue;
            try {
                $category = $this->getCategoryObject($model);
            } catch(\Exception $e) {
                continue;
            }
            if($category !== null) {
                $counter++;
                $categories->{$counter} = $category;
            }
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
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function show($id): JSONResponse {
        /** @var Tag $tag */
        $tag = $this->tagService->findByIdOrUuid($id);
        if($tag === null || $tag->isSuspended()) return new JSONResponse('Entity not found', 404);
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
     * @throws \Exception
     * @throws \OCA\Passwords\Exception\ApiException
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function create($categoryName, $categoryColour): JSONResponse {
        /** @var Tag $model */
        $model    = $this->tagService->create();
        $revision = $this->tagRevisionService->create(
            $model->getUuid(), $categoryName, '#'.$categoryColour, EncryptionService::CSE_ENCRYPTION_NONE, false, false, false
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
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function destroy($id): JSONResponse {
        /** @var Tag $tag */
        $tag = $this->tagService->findByIdOrUuid($id);
        if($tag === null || $tag->isSuspended()) return new JSONResponse('Entity not found', 404);
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
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
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
        if($revision->getSseType() !== EncryptionService::SSE_ENCRYPTION_V1) {
            return null;
        }

        return [
            'id'             => $tag->getId(),
            'user_id'        => $tag->getUserId(),
            'category_name'  => $revision->getLabel(),
            'category_colour' => substr($revision->getColor(), 1)
        ];
    }
}
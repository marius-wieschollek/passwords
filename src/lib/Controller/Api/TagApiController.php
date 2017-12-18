<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 19:45
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Helper\ApiObjects\TagObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use \OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class TagApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class TagApiController extends AbstractApiController {

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var TagObjectHelper
     */
    protected $tagObjectHelper;

    /**
     * PasswordApiController constructor.
     *
     * @param string             $appName
     * @param IRequest           $request
     * @param TagService      $tagService
     * @param TagObjectHelper $tagObjectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        TagService $tagService,
        TagObjectHelper $tagObjectHelper
    ) {
        parent::__construct(
            $appName,
            $request,
            'PUT, POST, GET, DELETE, PATCH',
            'Authorization, Content-Type, Accept',
            1728000
        );
        $this->tagService      = $tagService;
        $this->tagObjectHelper = $tagObjectHelper;
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
            $tags = $this->tagService->getAllTags();
            $results = [];

            foreach ($tags as $tag) {
                $object = $this->tagObjectHelper->getApiObject($tag, $details);

                if(!$object['hidden'] && !$object['trashed']) $results[] = $object;
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
            $tags = $this->tagService->getAllTags();
            $results = [];

            foreach ($tags as $tag) {
                $object = $this->tagObjectHelper->getApiObject($tag, $details);
                if(!$object['hidden']) continue;

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
            $model  = $this->tagService->getTagByUuid($id);
            $tag = $this->tagObjectHelper->getApiObject($model, $details);

            return $this->createJsonResponse($tag);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $label
     * @param string $color
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     */
    public function create(
        string $label,
        string $color,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {

        try {
            $model = $this->tagService->createTag($label, $color, $cseType, $sseType, $hidden, false, false, $favourite);
            $model = $this->tagService->saveTag($model);

            return $this->createJsonResponse(['tag' => $model->getUuid()], Http::STATUS_CREATED);
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
     * @param string $color
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
        string $color,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {

        try {
            $model = $this->tagService->getTagByUuid($id);
            $model->setName($name);
            $model->setColor($color);
            $model->setCseType($cseType);
            $model->setSseType($sseType);
            $model->setHidden($hidden);
            $model->setFavourite($favourite);
            $model = $this->tagService->saveTag($model);

            return $this->createJsonResponse(['tag' => $model->getUuid()]);
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
            $model = $this->tagService->getTagByUuid($id);

            if(!$model->isTrashed()) {
                $model->setTrashed(true);
            } else {
                $model->setDeleted(true);
                // @TODO Remove from all passwords
            }

            $this->tagService->saveTag($model);

            return $this->createJsonResponse(['tag' => $model->getUuid()]);
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
            $model = $this->tagService->getTagByUuid($id);

            if($model->isTrashed()) {
                $model->setTrashed(false);
                // @TODO Release relations
                $this->tagService->saveTag($model);
            }

            return $this->createJsonResponse(['tag' => $model->getUuid()]);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }
}
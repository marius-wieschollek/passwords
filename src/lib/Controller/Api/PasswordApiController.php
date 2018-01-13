<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:09
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\PasswordObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class PasswordApiController
 *
 * @package OCA\Passwords\Controller
 */
class PasswordApiController extends AbstractObjectApiController {

    /**
     * @var PasswordService
     */
    protected $modelService;

    /**
     * @var PasswordRevisionService
     */
    protected $revisionService;

    /**
     * @var PasswordObjectHelper
     */
    protected $objectHelper;

    /**
     * @var PasswordTagRelationService
     */
    protected $relationService;

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var TagRevisionService
     */
    protected $tagRevisionService;

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'cseType', 'sseType', 'status', 'trashed', 'favourite'];

    /**
     * PasswordApiController constructor.
     *
     * @param IRequest                   $request
     * @param TagService                 $tagService
     * @param TagRevisionService         $tagRevisionService
     * @param PasswordService            $modelService
     * @param PasswordRevisionService    $revisionService
     * @param PasswordObjectHelper       $objectHelper
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(
        IRequest $request,
        TagService $tagService,
        PasswordService $modelService,
        PasswordObjectHelper $objectHelper,
        TagRevisionService $tagRevisionService,
        PasswordRevisionService $revisionService,
        PasswordTagRelationService $relationService
    ) {
        parent::__construct($request, $modelService, $objectHelper, $revisionService);

        $this->tagService         = $tagService;
        $this->relationService    = $relationService;
        $this->tagRevisionService = $tagRevisionService;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $password
     * @param string $username
     * @param string $cseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $folder
     * @param bool   $hidden
     * @param bool   $favourite
     * @param array  $tags
     *
     * @TODO     check folder access
     *
     * @return JSONResponse
     * @internal param array $folders
     */
    public function create(
        string $password,
        string $username = '',
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $hash = '',
        string $label = '',
        string $url = '',
        string $notes = '',
        string $folder = FolderService::BASE_FOLDER_UUID,
        bool $hidden = false,
        bool $favourite = false,
        array $tags = []
    ): JSONResponse {
        try {
            $this->checkAccessPermissions();
            $model    = $this->modelService->create();
            $revision = $this->revisionService->create(
                $model->getUuid(), $password, $username, $cseType, $hash, $label, $url, $notes, $folder, $hidden,
                false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($model, $revision);

            if(!empty($tags)) $this->updateTags($tags, $revision);

            return $this->createJsonResponse(
                ['id' => $model->getUuid(), 'revision' => $revision->getUuid()],
                Http::STATUS_CREATED
            );
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
     * @param string $password
     * @param string $username
     * @param string $cseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $folder
     * @param bool   $hidden
     * @param bool   $favourite
     * @param array  $tags
     *
     * @TODO check folder access
     *
     * @return JSONResponse
     */
    public function update(
        string $id,
        string $password,
        string $username = '',
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $hash = '',
        string $label = '',
        string $url = '',
        string $notes = '',
        string $folder = FolderService::BASE_FOLDER_UUID,
        bool $hidden = false,
        bool $favourite = false,
        array $tags = []
    ): JSONResponse {
        try {
            $this->checkAccessPermissions();

            /** @var Password $model */
            $model = $this->modelService->findByUuid($id);
            /** @var PasswordRevision $oldRevision */
            $oldRevision = $this->revisionService->findByUuid($model->getRevision(), true);

            if(!$model->isEditable()) {
                $password = $oldRevision->getPassword();
                $username = $oldRevision->getUsername();
                $cseType  = $oldRevision->getCseType();
                $label    = $oldRevision->getLabel();
                $notes    = $oldRevision->getNotes();
                $hash     = $oldRevision->getHash();
                $url      = $oldRevision->getUrl();
            } else if(($model->hasShares() || $model->getShareId())) {
                if($cseType !== EncryptionService::CSE_ENCRYPTION_NONE) {
                    throw new ApiException('CSE type does not support sharing', 400);
                }
                if($hidden) {
                    throw new ApiException('Shared password can not be hidden', 400);
                }
            }

            $revision = $this->revisionService->create(
                $model->getUuid(), $password, $username, $cseType, $hash, $label, $url, $notes, $folder, $hidden, $oldRevision->isTrashed(),
                $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($model, $revision);

            if(!empty($tags)) $this->updateTags($tags, $revision);

            return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $revision->getUuid()]);
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

            if($revision !== null) {
                /** @var Password $model */
                $model = $this->modelService->findByUuid($id);

                if($model->hasShares() || $model->getShareId()) {
                    /** @var PasswordRevision $revision */
                    $revision = $this->revisionService->findByUuid($revision);

                    if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) {
                        throw new ApiException('CSE type does not support sharing', 400);
                    }
                    if($revision->isHidden()) {
                        throw new ApiException('Shared password can not be hidden', 400);
                    }
                }
            }

            return parent::restore($id, $revision);
        } catch(\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }

    /**
     * @param                  $tags
     * @param PasswordRevision $passwordRevision
     *
     * @throws \Exception
     */
    protected function updateTags($tags, PasswordRevision $passwordRevision) {
        $skip         = [];
        $tagRelations = $this->relationService->findByPassword($passwordRevision->getModel());

        foreach($tagRelations as $tagRelation) {
            if(in_array($tagRelation->getTag(), $tags)) {
                $skip[] = $tagRelation->getTag();
                continue;
            }

            $this->relationService->delete($tagRelation);
        }

        foreach($tags as $tag) {
            if(in_array($tag, $skip) || empty($tag)) continue;
            $tag = $this->tagService->findByUuid($tag);
            /** @var TagRevision $revision */
            $revision = $this->tagRevisionService->findByUuid($tag->getRevision());

            $relation = $this->relationService->create($passwordRevision, $revision);
            $this->relationService->save($relation);
        }
    }
}
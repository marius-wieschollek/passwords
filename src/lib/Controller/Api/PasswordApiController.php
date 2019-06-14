<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
use OCA\Passwords\Services\ValidationService;
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
     * @var TagService
     */
    protected $tagService;

    /**
     * @var PasswordService
     */
    protected $modelService;

    /**
     * @var PasswordObjectHelper
     */
    protected $objectHelper;

    /**
     * @var PasswordRevisionService
     */
    protected $revisionService;

    /**
     * @var PasswordTagRelationService
     */
    protected $relationService;

    /**
     * @var TagRevisionService
     */
    protected $tagRevisionService;

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'edited', 'cseType', 'sseType', 'status', 'trashed', 'favorite'];

    /**
     * PasswordApiController constructor.
     *
     * @param IRequest                   $request
     * @param TagService                 $tagService
     * @param PasswordService            $modelService
     * @param PasswordObjectHelper       $objectHelper
     * @param ValidationService          $validationService
     * @param TagRevisionService         $tagRevisionService
     * @param PasswordRevisionService    $revisionService
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(
        IRequest $request,
        TagService $tagService,
        PasswordService $modelService,
        PasswordObjectHelper $objectHelper,
        ValidationService $validationService,
        TagRevisionService $tagRevisionService,
        PasswordRevisionService $revisionService,
        PasswordTagRelationService $relationService
    ) {
        parent::__construct($request, $modelService, $objectHelper, $validationService, $revisionService);

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
     * @param string $cseKey
     * @param string $cseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $customFields
     * @param string $folder
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $favorite
     * @param array  $tags
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \Exception
     */
    public function create(
        string $password,
        string $username = '',
        string $cseKey = '',
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $hash = '',
        string $label = '',
        string $url = '',
        string $notes = '',
        string $customFields = '',
        string $folder = FolderService::BASE_FOLDER_UUID,
        int $edited = 0,
        bool $hidden = false,
        bool $favorite = false,
        array $tags = []
    ): JSONResponse {
        if($edited < 1) $edited = time();

        $model    = $this->modelService->create();
        $revision = $this->revisionService->create(
            $model->getUuid(), $password, $username, $cseKey, $cseType, $hash, $label, $url, $notes, $customFields, $folder, $edited, $hidden,
            false, $favorite
        );

        $this->revisionService->save($revision);
        $this->modelService->setRevision($model, $revision);

        if(!empty($tags)) $this->updateTags($tags, $revision);

        return $this->createJsonResponse(
            ['id' => $model->getUuid(), 'revision' => $revision->getUuid()],
            Http::STATUS_CREATED
        );
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param string $password
     * @param string $username
     * @param string $cseKey
     * @param string $cseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $customFields
     * @param string $folder
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $favorite
     * @param array  $tags
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function update(
        string $id,
        string $password,
        string $username = '',
        string $cseKey = '',
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $hash = '',
        string $label = '',
        string $url = '',
        string $notes = '',
        string $customFields = '',
        string $folder = FolderService::BASE_FOLDER_UUID,
        int $edited = 0,
        bool $hidden = false,
        bool $favorite = false,
        array $tags = []
    ): JSONResponse {
        /** @var Password $model */
        $model = $this->modelService->findByUuid($id);
        /** @var PasswordRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($model->getRevision(), true);

        if(!$model->isEditable()) {
            $customFields = $oldRevision->getCustomFields();
            $password     = $oldRevision->getPassword();
            $username     = $oldRevision->getUsername();
            $cseType      = $oldRevision->getCseType();
            $edited       = $oldRevision->getEdited();
            $label        = $oldRevision->getLabel();
            $notes        = $oldRevision->getNotes();
            $hash         = $oldRevision->getHash();
            $url          = $oldRevision->getUrl();
        } else if($model->hasShares() || $model->getShareId()) {
            if($cseType !== EncryptionService::CSE_ENCRYPTION_NONE) {
                throw new ApiException('CSE type does not support sharing', Http::STATUS_BAD_REQUEST);
            }
            if($hidden) {
                throw new ApiException('Shared entity can not be hidden', Http::STATUS_BAD_REQUEST);
            }
        }

        $revision = $this->revisionService->create(
            $model->getUuid(), $password, $username, $cseKey, $cseType, $hash, $label, $url, $notes, $customFields, $folder, $edited, $hidden, $oldRevision->isTrashed(),
            $favorite
        );

        if($revision->getHash() !== $oldRevision->getHash()) {
            if($edited < 1 || $revision->getEdited() === $oldRevision->getEdited()) $revision->setEdited(time());
        } else {
            $revision->setEdited($oldRevision->getEdited());
        }

        if($model->hasShares() || $model->getShareId() || !$model->isEditable()) {
            $revision->setSseType($oldRevision->getSseType());
        }

        $this->revisionService->save($revision);
        $this->modelService->setRevision($model, $revision);

        if(!empty($tags)) $this->updateTags($tags, $revision);

        return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $revision->getUuid()]);
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
        if($revision !== null) {
            /** @var Password $model */
            $model = $this->modelService->findByUuid($id);

            if($model->hasShares() || $model->getShareId()) {
                /** @var PasswordRevision $revision */
                $revision = $this->revisionService->findByUuid($revision);

                if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) {
                    throw new ApiException('CSE type does not support sharing', Http::STATUS_BAD_REQUEST);
                }
                if($revision->getSseType() !== EncryptionService::SSE_ENCRYPTION_V1R2) {
                    throw new ApiException('SSE type does not support sharing', Http::STATUS_BAD_REQUEST);
                }
                if($revision->isHidden()) {
                    throw new ApiException('Shared entity can not be hidden', Http::STATUS_BAD_REQUEST);
                }
            }
        }

        return parent::restore($id, $revision);
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
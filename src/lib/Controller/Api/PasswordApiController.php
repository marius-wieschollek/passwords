<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:09
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Helper\ApiObjects\PasswordObjectHelper;
use OCA\Passwords\Services\EncryptionService;
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
    private $relationService;
    /**
     * @var TagService
     */
    private $tagService;
    /**
     * @var TagRevisionService
     */
    private $tagRevisionService;

    /**
     * PasswordApiController constructor.
     *
     * @param string                     $appName
     * @param IRequest                   $request
     * @param TagService                 $tagService
     * @param TagRevisionService         $tagRevisionService
     * @param PasswordService            $modelService
     * @param PasswordRevisionService    $revisionService
     * @param PasswordObjectHelper       $objectHelper
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(
        $appName,
        IRequest $request,
        TagService $tagService,
        PasswordService $modelService,
        TagRevisionService $tagRevisionService,
        PasswordRevisionService $revisionService,
        PasswordObjectHelper $objectHelper,
        PasswordTagRelationService $relationService
    ) {
        parent::__construct($appName, $request, $modelService, $revisionService, $objectHelper);

        $this->tagService         = $tagService;
        $this->relationService    = $relationService;
        $this->tagRevisionService = $tagRevisionService;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $password
     * @param string $username
     * @param string $cseType
     * @param string $sseType
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
     * @TODO     check folder is system trash
     *
     * @return JSONResponse
     * @internal param array $folders
     */
    public function create(
        string $password,
        string $username = '',
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        string $hash = '',
        string $label = '',
        string $url = '',
        string $notes = '',
        string $folder = '00000000-0000-0000-0000-000000000000',
        bool $hidden = false,
        bool $favourite = false,
        array $tags = []
    ): JSONResponse {

        try {
            $model    = $this->modelService->create();
            $revision = $this->revisionService->createRevision(
                $model->getUuid(), $password, $username, $cseType, $sseType, $hash, $label, $url, $notes, $folder, $hidden,
                false, false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($model, $revision);

            if(!empty($tags)) $this->updateTags($tags, $revision);

            return $this->createJsonResponse(
                ['id' => $model->getUuid(), 'revision' => $revision->getUuid()],
                Http::STATUS_CREATED
            );
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param string $password
     * @param string $username
     * @param string $cseType
     * @param string $sseType
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
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        string $hash = '',
        string $label = '',
        string $url = '',
        string $notes = '',
        string $folder = '00000000-0000-0000-0000-000000000000',
        bool $hidden = false,
        bool $favourite = false,
        array $tags = []
    ): JSONResponse {

        try {
            $model = $this->modelService->findByUuid($id);

            $revision = $this->revisionService->createRevision(
                $model->getUuid(), $password, $username, $cseType, $sseType, $hash, $label, $url, $notes, $folder, $hidden,
                false, false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($model, $revision);

            if(!empty($tags)) $this->updateTags($tags, $revision);

            return $this->createJsonResponse(['id' => $model->getUuid(), 'revision' => $revision->getUuid()]);
        } catch (\Throwable $e) {

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

        foreach ($tagRelations as $tagRelation) {
            if(in_array($tagRelation->getTag(), $tags)) {
                $skip[] = $tagRelation->getTag();
                continue;
            }

            $this->relationService->delete($tagRelation);
        }

        foreach ($tags as $tag) {
            if(in_array($tag, $skip) || empty($tag)) continue;
            $tag = $this->tagService->findByUuid($tag);
            /** @var TagRevision $revision */
            $revision = $this->tagRevisionService->findByUuid($tag->getRevision(), false);

            $relation = $this->relationService->create($passwordRevision, $revision);
            $this->relationService->save($relation);
        }
    }
}
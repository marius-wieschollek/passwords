<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:09
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Helper\ApiObjects\PasswordObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
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
     * PasswordApiController constructor.
     *
     * @param string                  $appName
     * @param IRequest                $request
     * @param PasswordService         $modelService
     * @param PasswordRevisionService $revisionService
     * @param PasswordObjectHelper    $objectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        PasswordService $modelService,
        PasswordRevisionService $revisionService,
        PasswordObjectHelper $objectHelper
    ) {
        parent::__construct($appName, $request, $modelService, $revisionService, $objectHelper);
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
     * @TODO     check is system trash
     * @TODO     check tag access
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

            return $this->createJsonResponse(
                ['password' => $model->getUuid(), 'revision' => $revision->getUuid()],
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
     * @TODO check is system trash
     * @TODO check tag access
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

            return $this->createJsonResponse(['password' => $model->getUuid(), 'revision' => $revision->getUuid()]);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }
}
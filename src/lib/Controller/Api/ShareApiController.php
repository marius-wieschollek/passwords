<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Db\Share;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\AbstractObjectHelper;
use OCA\Passwords\Helper\ApiObjects\ShareObjectHelper;
use OCA\Passwords\Helper\Settings\ShareSettingsHelper;
use OCA\Passwords\Helper\Sharing\CreatePasswordShareHelper;
use OCA\Passwords\Helper\Sharing\ShareUserListHelper;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class ShareApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class ShareApiController extends AbstractApiController {

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var ShareService
     */
    protected $modelService;

    /**
     * @var ShareObjectHelper
     */
    protected $objectHelper;

    /**
     * @var ShareSettingsHelper
     */
    protected $shareSettings;

    /**
     * @var ShareUserListHelper
     */
    protected $shareUserList;

    /**
     * @var CreatePasswordShareHelper
     */
    protected $createPasswordShare;

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'userId', 'receiver', 'expires', 'editable', 'shareable'];

    /**
     * ShareApiController constructor.
     *
     * @param null|string               $userId
     * @param IRequest                  $request
     * @param ShareService              $modelService
     * @param ShareObjectHelper         $objectHelper
     * @param ShareSettingsHelper       $shareSettings
     * @param ShareUserListHelper       $shareUserList
     * @param CreatePasswordShareHelper $createPasswordShare
     */
    public function __construct(
        ?string $userId,
        IRequest $request,
        ShareService $modelService,
        ShareObjectHelper $objectHelper,
        ShareSettingsHelper $shareSettings,
        ShareUserListHelper $shareUserList,
        CreatePasswordShareHelper $createPasswordShare
    ) {
        parent::__construct($request);

        $this->userId              = $userId;
        $this->modelService        = $modelService;
        $this->objectHelper        = $objectHelper;
        $this->shareSettings       = $shareSettings;
        $this->shareUserList       = $shareUserList;
        $this->createPasswordShare = $createPasswordShare;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $details
     *
     * @return JSONResponse
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    public function list(string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        /** @var Share[] $models */
        $models = $this->modelService->findAll();

        $results = [];
        foreach($models as $model) {
            $results[] = $this->objectHelper->getApiObject($model, $details);
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
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    public function find($criteria = [], string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
        $filters = $this->processSearchCriteria($criteria);
        /** @var Share[] $models */
        $models = $this->modelService->findAll();

        $results = [];
        foreach($models as $model) {
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
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
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
     * @param string   $password
     * @param string   $receiver
     * @param string   $type
     * @param int|null $expires
     * @param bool     $editable
     * @param bool     $shareable
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function create(
        string $password,
        string $receiver,
        string $type = 'user',
        int $expires = null,
        bool $editable = false,
        bool $shareable = false
    ): JSONResponse {
        $this->checkAccessPermissions();

        $receiver = $this->shareUserList->mapReceiverToUid($receiver);
        if(!$this->shareUserList->canShareWithUser($receiver)) throw new ApiException('Invalid receiver uid', 400);

        $share = $this->createPasswordShare->createPasswordShare(
            $password,
            $receiver,
            $type,
            $expires,
            $editable,
            $shareable
        );

        return $this->createJsonResponse(['id' => $share->getUuid()], Http::STATUS_CREATED);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string   $id
     * @param int|null $expires
     * @param bool     $editable
     * @param bool     $shareable
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function update(string $id, int $expires = null, bool $editable = false, bool $shareable = true): JSONResponse {
        $this->checkAccessPermissions();

        if(empty($expires)) $expires = null;
        if($expires !== null && $expires < time()) {
            throw new ApiException('Invalid expiration date', 400);
        }

        $share = $this->modelService->findByUuid($id);
        if($share->getUserId() !== $this->userId) {
            throw new ApiException('Access denied', 403);
        }

        $share->setExpires($expires);
        $share->setEditable($editable);
        $share->setShareable($shareable);
        $share->setSourceUpdated(true);
        $this->modelService->save($share);

        return $this->createJsonResponse(['id' => $share->getUuid()]);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function delete(string $id): JSONResponse {
        $model = $this->modelService->findByUuid($id);
        if($model->getUserId() !== $this->userId) {
            throw new ApiException('Access denied', 403);
        }

        $this->modelService->delete($model);

        return $this->createJsonResponse(['id' => $model->getUuid()]);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @UserRateThrottle(limit=45, period=60)
     *
     * @param string $search
     * @param int    $limit
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function partners(string $search = '', int $limit = 5): JSONResponse {
        $this->checkAccessPermissions();

        $partners = [];
        if($this->shareSettings->get('autocomplete')) {
            $partners = $this->shareUserList->getShareUsers($search, $limit);
        }

        return $this->createJsonResponse($partners);
    }

    /**
     * @param array $criteria
     *
     * @return array
     * @throws ApiException
     */
    protected function processSearchCriteria($criteria = []): array {
        if(isset($criteria['owner'])) {
            $criteria['userId'] = $criteria['owner'];
            unset($criteria['owner']);
        }

        if(isset($criteria['userId'])) {
            if(is_array($criteria['userId']) && $criteria['userId'][1] === '_self') {
                $criteria['userId'][1] = $this->userId;
            } else if($criteria['userId'] === '_self') {
                $criteria['userId'] = $this->userId;
            }
        }

        if(isset($criteria['receiver'])) {
            if(is_array($criteria['receiver']) && $criteria['receiver'][1] === '_self') {
                $criteria['receiver'][1] = $this->userId;
            } else if($criteria['receiver'] === '_self') {
                $criteria['receiver'] = $this->userId;
            }
        }

        return parent::processSearchCriteria($criteria);
    }

    /**
     * @throws ApiException
     */
    protected function checkAccessPermissions(): void {
        if(!$this->shareSettings->get('enabled')) throw new ApiException('Sharing disabled', 403);
    }
}
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
use OCA\Passwords\Db\Share;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\AbstractObjectHelper;
use OCA\Passwords\Helper\ApiObjects\ShareObjectHelper;
use OCA\Passwords\Helper\Settings\ShareSettingsHelper;
use OCA\Passwords\Helper\Sharing\CreatePasswordShareHelper;
use OCA\Passwords\Helper\Sharing\ShareUserListHelper;
use OCA\Passwords\Helper\Sharing\UpdatePasswordShareHelper;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class ShareApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class ShareApiController extends AbstractApiController {

    /**
     * @var string|null
     */
    protected ?string $userId;

    /**
     * @var ShareService
     */
    protected ShareService $modelService;

    /**
     * @var ShareObjectHelper
     */
    protected ShareObjectHelper $objectHelper;

    /**
     * @var ShareSettingsHelper
     */
    protected ShareSettingsHelper $shareSettings;

    /**
     * @var ShareUserListHelper
     */
    protected ShareUserListHelper $shareUserList;

    /**
     * @var CreatePasswordShareHelper
     */
    protected CreatePasswordShareHelper $createPasswordShare;

    /**
     * @var UpdatePasswordShareHelper
     */
    protected UpdatePasswordShareHelper $updatePasswordShareHelper;

    /**
     * @var array
     */
    protected array $allowedFilterFields = ['created', 'updated', 'userId', 'receiver', 'expires', 'editable', 'shareable'];

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
     * @param UpdatePasswordShareHelper $updatePasswordShareHelper
     */
    public function __construct(
        ?string $userId,
        IRequest $request,
        ShareService $modelService,
        ShareObjectHelper $objectHelper,
        ShareSettingsHelper $shareSettings,
        ShareUserListHelper $shareUserList,
        CreatePasswordShareHelper $createPasswordShare,
        UpdatePasswordShareHelper $updatePasswordShareHelper
    ) {
        parent::__construct($request);

        $this->userId                    = $userId;
        $this->modelService              = $modelService;
        $this->objectHelper              = $objectHelper;
        $this->shareSettings             = $shareSettings;
        $this->shareUserList             = $shareUserList;
        $this->createPasswordShare       = $createPasswordShare;
        $this->updatePasswordShareHelper = $updatePasswordShareHelper;
    }

    /**
     * @param string $details
     *
     * @return JSONResponse
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
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
     * @param array  $criteria
     * @param string $details
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
    public function find(array $criteria = [], string $details = AbstractObjectHelper::LEVEL_MODEL): JSONResponse {
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
     * @param string $id
     * @param string $details
     *
     * @return JSONResponse
     * @throws Exception
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
     * @param string   $password
     * @param string   $receiver
     * @param string   $type
     * @param int|null $expires
     * @param bool     $editable
     * @param bool     $shareable
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
        if(!$this->shareUserList->canShareWithUser($receiver)) throw new ApiException('Invalid receiver uid', Http::STATUS_BAD_REQUEST);

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
     * @param string   $id
     * @param int|null $expires
     * @param bool     $editable
     * @param bool     $shareable
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function update(string $id, int $expires = null, bool $editable = false, bool $shareable = true): JSONResponse {
        $this->checkAccessPermissions();

        if(empty($expires)) $expires = null;
        if($expires !== null && $expires < time()) {
            throw new ApiException('Invalid expiration date', Http::STATUS_BAD_REQUEST);
        }

        $share = $this->modelService->findByUuid($id);
        if($share->getUserId() !== $this->userId) {
            throw new ApiException('Access denied', Http::STATUS_FORBIDDEN);
        }

        $share = $this->updatePasswordShareHelper->updatePasswordShare($share, $expires, $editable, $shareable);

        return $this->createJsonResponse(['id' => $share->getUuid()]);
    }

    /**
     * @param string $id
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
    public function delete(string $id): JSONResponse {
        $model = $this->modelService->findByUuid($id);
        if($model->getUserId() !== $this->userId) {
            throw new ApiException('Access denied', Http::STATUS_FORBIDDEN);
        }

        $this->modelService->delete($model);

        return $this->createJsonResponse(['id' => $model->getUuid()]);
    }

    /**
     * @param string $search
     * @param int    $limit
     *
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[UserRateLimit(limit: 20, period: 30)]
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
    protected function processSearchCriteria(array $criteria = []): array {
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
        if(!$this->shareSettings->get('enabled')) throw new ApiException('Sharing disabled', Http::STATUS_FORBIDDEN);
    }
}
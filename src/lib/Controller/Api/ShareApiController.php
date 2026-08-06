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
use OCA\Passwords\Helper\Sharing\RecipientSearchHelper;
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
     * @var array
     */
    protected array $allowedFilterFields = ['created', 'updated', 'userId', 'receiver', 'expires', 'editable', 'shareable', 'type'];

    /**
     * ShareApiController constructor.
     *
     * @param null|string               $userId
     * @param IRequest                  $request
     * @param ShareService              $modelService
     * @param ShareObjectHelper         $objectHelper
     * @param ShareSettingsHelper       $shareSettings
     * @param RecipientSearchHelper     $shareUserList
     * @param CreatePasswordShareHelper $createPasswordShare
     * @param UpdatePasswordShareHelper $updatePasswordShareHelper
     */
    public function __construct(
        protected ?string                   $userId,
        IRequest                            $request,
        protected ShareService              $modelService,
        protected ShareObjectHelper         $objectHelper,
        protected ShareSettingsHelper       $shareSettings,
        protected RecipientSearchHelper     $shareUserList,
        protected CreatePasswordShareHelper $createPasswordShare,
        protected UpdatePasswordShareHelper $updatePasswordShareHelper
    ) {
        parent::__construct($request);
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
     * @param string      $password
     * @param string|null $recipient
     * @param string      $type
     * @param int|null    $expires
     * @param bool        $editable
     * @param bool        $shareable
     * @param string|null $receiver
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function create(
        string $password,
        string $recipient = null,
        string $type = 'user',
        ?int   $expires = null,
        bool   $editable = false,
        bool   $shareable = false,
        string $receiver = null,
    ): JSONResponse {
        $this->checkAccessPermissions();

        /**
         * Map deprecated $receiver property
         */
        if($recipient === null) {
            if($receiver !== null) {
                $recipient = $receiver;
            } else {
                throw new ApiException('Invalid recipient uid', Http::STATUS_BAD_REQUEST);
            }
        }

        if($type === Share::TYPE_GROUP) {
            if(!$this->shareUserList->canShareWithGroup($recipient)) throw new ApiException('Invalid recipient group', Http::STATUS_BAD_REQUEST);
        } else {
            $recipient = $this->shareUserList->mapRecipientToUid($recipient);
            if(!$this->shareUserList->canShareWithUser($recipient)) throw new ApiException('Invalid recipient uid', Http::STATUS_BAD_REQUEST);
        }

        $share = $this->createPasswordShare->createPasswordShare(
            $password,
            $recipient,
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
    public function update(string $id, ?int $expires = null, bool $editable = false, bool $shareable = false): JSONResponse {
        $this->checkAccessPermissions();

        if(empty($expires)) $expires = null;
        if($expires !== null && $expires < time()) {
            throw new ApiException('Invalid expiration date', Http::STATUS_BAD_REQUEST);
        }

        $share = $this->modelService->findByUuid($id);
        if($share->getUserId() !== $this->userId) {
            throw new ApiException('Access denied', Http::STATUS_FORBIDDEN);
        }
        $this->checkIsNotDerived($share);

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
        $this->checkIsNotDerived($model);

        $this->modelService->delete($model);

        return $this->createJsonResponse(['id' => $model->getUuid()]);
    }

    /**
     * The shares of the members of a group share belong to the group share.
     * Changing or deleting them directly would be undone by the next cron run,
     * so it is rejected instead of silently ignored.
     *
     * @param Share $share
     *
     * @throws ApiException
     */
    protected function checkIsNotDerived(Share $share): void {
        if($share->isDerived()) {
            throw new ApiException('Share belongs to a group share', Http::STATUS_BAD_REQUEST);
        }
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
            $matches = $this->shareUserList->findRecipientSuggestions($search, $limit);

            foreach($matches as $match) {
                if($match['type'] === 'user') {
                    $partners[ $match['id'] ] = $match['name'];
                }
            }
        }

        return $this->createJsonResponse($partners);
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param bool   $withGroups
     *
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[UserRateLimit(limit: 20, period: 30)]
    public function recipients(string $search = '', int $limit = 5, bool $withGroups = true): JSONResponse {
        $this->checkAccessPermissions();

        return $this->createJsonResponse(
            $this->shareUserList->findRecipientSuggestions($search, $limit, $withGroups)
        );
    }

    /**
     * @param string $groupId
     *
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[UserRateLimit(limit: 20, period: 30)]
    public function resolveGroup(string $groupId): JSONResponse {
        $this->checkAccessPermissions();

        return $this->createJsonResponse(
            $this->shareUserList->resolveGroup($groupId)
        );
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
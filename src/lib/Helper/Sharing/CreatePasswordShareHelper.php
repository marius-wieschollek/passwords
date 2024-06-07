<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Sharing;

use Exception;
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Settings\ShareSettingsHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;

/**
 * Class CreatePasswordShareHelper
 *
 * @package OCA\Passwords\Helper\Share
 */
class CreatePasswordShareHelper {

    /**
     * @var ShareService
     */
    protected ShareService $modelService;

    /**
     * @var ShareSettingsHelper
     */
    protected ShareSettingsHelper $shareSettings;

    /**
     * @var PasswordService
     */
    protected PasswordService $passwordModelService;

    /**
     * @var PasswordRevisionService
     */
    protected PasswordRevisionService $passwordRevisionService;

    /**
     * CreatePasswordShareHelper constructor.
     *
     * @param ShareService            $modelService
     * @param ShareSettingsHelper     $shareSettings
     * @param PasswordService         $passwordModelService
     * @param PasswordRevisionService $passwordRevisionService
     */
    public function __construct(
        ShareService $modelService,
        ShareSettingsHelper $shareSettings,
        PasswordService $passwordModelService,
        PasswordRevisionService $passwordRevisionService
    ) {
        $this->modelService            = $modelService;
        $this->shareSettings           = $shareSettings;
        $this->passwordModelService    = $passwordModelService;
        $this->passwordRevisionService = $passwordRevisionService;
    }

    /**
     * @param string   $password
     * @param string   $receiver
     * @param string   $type
     * @param int|null $expires
     * @param bool     $editable
     * @param bool     $shareable
     *
     * @return ModelInterface|Share
     * @throws ApiException
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function createPasswordShare(
        string $password,
        string $receiver,
        string $type = 'user',
        int $expires = null,
        bool $editable = false,
        bool $shareable = false
    ) {
        $expires = $this->checkExpires($expires);
        $this->checkType($type);

        /** @var Password $model */
        $model = $this->passwordModelService->findByUuid($password);
        if($model->getShareId()) [$editable, $expires] = $this->checkSourceShare($receiver, $model, $editable, $expires);

        $this->checkIfAlreadyShared($receiver, $model);

        /** @var PasswordRevision $revision */
        $revision = $this->passwordRevisionService->findByUuid($model->getRevision(), true);
        $this->checkIfRevisionCanBeShared($revision);

        if($revision->getSseType() !== EncryptionService::SSE_ENCRYPTION_V1R1) {
            $this->downgradeSSE($revision, $model);
        }

        $share = $this->modelService->create($model->getUuid(), $receiver, $type, $editable, $expires, $shareable);
        $this->modelService->save($share);

        $this->setHasSharesFlag($model);

        return $share;
    }

    /**
     * @param int|null $expires
     *
     * @return int|null
     * @throws ApiException
     */
    protected function checkExpires(?int $expires) {
        if(empty($expires)) $expires = null;
        if($expires !== null && $expires < time()) throw new ApiException('Invalid expiration date', Http::STATUS_BAD_REQUEST);

        return $expires;
    }

    /**
     * @param string $type
     *
     * @throws ApiException
     */
    protected function checkType(string $type): void {
        if($type !== 'user') throw new ApiException('Invalid share type', Http::STATUS_BAD_REQUEST);
    }

    /**
     * @param string $receiver
     * @param        $model
     *
     * @return bool
     * @throws ApiException
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    protected function checkSourceShare(string $receiver, Password $model, bool $editable, ?int $expires): array {
        $sourceShare = $this->modelService->findByUuid($model->getShareId());
        if($sourceShare->getUserId() === $receiver) throw new ApiException('Invalid receiver uid', Http::STATUS_BAD_REQUEST);
        if(!$sourceShare->isShareable() || !$this->shareSettings->get('resharing')) throw new ApiException('Entity not shareable', Http::STATUS_FORBIDDEN);
        if(!$sourceShare->isEditable()) $editable = false;
        if($sourceShare->getExpires() !== null) {
            if($expires === null) {
                $expires = $sourceShare->getExpires();
            } else if($expires > $sourceShare->getExpires()) {
                $expires = $sourceShare->getExpires();
            }
        }

        return [$editable, $expires];
    }

    /**
     * @param string $receiver
     * @param        $model
     *
     * @throws ApiException
     * @throws MultipleObjectsReturnedException
     */
    protected function checkIfAlreadyShared(string $receiver, Password $model): void {
        try {
            $shares = $this->modelService->findBySourcePasswordAndReceiver($model->getUuid(), $receiver);
            if($shares !== null) throw new ApiException('Entity already shared with user', Http::STATUS_BAD_REQUEST);
        } catch(DoesNotExistException $e) {
        }
    }

    /**
     * @param $revision
     *
     * @throws ApiException
     */
    protected function checkIfRevisionCanBeShared(PasswordRevision $revision): void {
        if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) throw new ApiException('CSE type does not support sharing', 420);
        if($revision->isHidden()) throw new ApiException('Shared entity can not be hidden', 420);
    }

    /**
     * @param $revision
     * @param $model
     *
     * @throws Exception
     */
    protected function downgradeSSE(PasswordRevision $revision, Password $model): void {
        /** @var PasswordRevision $newRevision */
        $newRevision = $this->passwordRevisionService->clone(
            $revision,
            ['sseType' => EncryptionService::SSE_ENCRYPTION_V1R1]
        );
        $this->passwordRevisionService->save($newRevision);
        $this->passwordModelService->setRevision($model, $newRevision);
    }

    /**
     * @param $model
     */
    protected function setHasSharesFlag(Password $model): void {
        if(!$model->hasShares()) {
            $model->setHasShares(true);
            $this->passwordModelService->save($model);
        }
    }
}
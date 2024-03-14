<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\IAppContainer;
use Throwable;

/**
 * Class ValidationService
 *
 * @package OCA\Passwords\Services
 */
class ValidationService {

    /**
     * @var IAppContainer
     */
    protected IAppContainer $container;

    /**
     * ValidationService constructor.
     *
     * @param IAppContainer $container
     */
    public function __construct(IAppContainer $container) {
        $this->container = $container;
    }

    /**
     * @param PasswordRevision $password
     *
     * @return PasswordRevision
     * @throws ApiException
     */
    public function validatePassword(PasswordRevision $password): PasswordRevision {
        $this->validateEncryptionSettings($password);

        if(empty($password->getLabel())) {
            throw new ApiException('Field "label" can not be empty', Http::STATUS_BAD_REQUEST);
        }
        if(empty($password->getPassword())) {
            throw new ApiException('Field "password" can not be empty', Http::STATUS_BAD_REQUEST);
        }
        $this->checkHash($password);
        if(empty($password->getEdited()) || $password->getEdited() > strtotime('+2 hour')) {
            $password->setEdited(time());
        }
        $password->setFolder(
            $this->validateFolderRelation($password->getFolder(), $password->isHidden())
        );

        return $password;
    }

    /**
     * @param FolderRevision $folder
     *
     * @return FolderRevision
     * @throws ApiException
     */
    public function validateFolder(FolderRevision $folder): FolderRevision {
        $this->validateEncryptionSettings($folder);

        if(empty($folder->getLabel())) {
            throw new ApiException('Field "label" can not be empty', Http::STATUS_BAD_REQUEST);
        }
        if(empty($folder->getEdited()) || $folder->getEdited() > strtotime('+1 hour')) {
            $folder->setEdited(time());
        }
        if($folder->getModel() === $folder->getParent()) {
            $folder->setParent(FolderService::BASE_FOLDER_UUID);
        } else {
            $folder->setParent(
                $this->validateFolderRelation($folder->getParent(), $folder->isHidden())
            );
        }

        return $folder;
    }

    /**
     * @param TagRevision $tag
     *
     * @return TagRevision
     * @throws ApiException
     */
    public function validateTag(TagRevision $tag): TagRevision {
        $this->validateEncryptionSettings($tag);

        if(empty($tag->getLabel())) {
            throw new ApiException('Field "label" can not be empty', Http::STATUS_BAD_REQUEST);
        }
        if(empty($tag->getColor())) {
            throw new ApiException('Field "color" can not be empty', Http::STATUS_BAD_REQUEST);
        }
        if(empty($tag->getEdited()) || $tag->getEdited() > strtotime('+1 hour')) {
            $tag->setEdited(time());
        }

        return $tag;
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws ApiException
     * @throws Exception
     */
    public function validateObject(RevisionInterface $object): RevisionInterface {

        switch(get_class($object)) {
            case PasswordRevision::class:
                /** @var $object PasswordRevision */
                return $this->validatePassword($object);
            case FolderRevision::class:
                /** @var $object FolderRevision */
                return $this->validateFolder($object);
            case TagRevision::class:
                /** @var $object TagRevision */
                return $this->validateTag($object);
        }

        throw new Exception('Unknown object type');
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    public function isValidDomain(string $domain): bool {
        if(!preg_match("/^(([\w_-]+\.)+[\w_-]+)(:[0-9]+)?$/", idn_to_ascii($domain), $matches)) return false;
        if(!checkdnsrr($matches[1], 'A')) return false;

        return true;
    }

    /**
     * @param string $uuid
     *
     * @return bool
     */
    public function isValidUuid(string $uuid): bool {
        return preg_match("/^[0-9a-z]{8}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{12}$/", $uuid) === 1;
    }

    /**
     * @param string $folderUuid
     * @param bool   $isHidden
     *
     * @return string
     */
    protected function validateFolderRelation(string $folderUuid, bool $isHidden): string {
        if($folderUuid !== FolderService::BASE_FOLDER_UUID) {
            if(!$this->isValidUuid($folderUuid)) {
                return FolderService::BASE_FOLDER_UUID;
            } else {
                try {
                    $folderRevisionService = $this->container->get(FolderRevisionService::class);
                    /** @var FolderRevision $folderRevision */
                    $folderRevision = $folderRevisionService->findCurrentRevisionByModel($folderUuid, false);
                    if($folderRevision->isHidden() && !$isHidden) {
                        return FolderService::BASE_FOLDER_UUID;
                    }
                } catch(Throwable $e) {
                    return FolderService::BASE_FOLDER_UUID;
                }
            }
        }

        return $folderUuid;
    }

    /**
     * @param RevisionInterface $revision
     *
     * @throws ApiException
     */
    protected function validateEncryptionSettings(RevisionInterface $revision): void {
        if(empty($revision->getSseType())) {
            $revision->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        }
        if(empty($revision->getCseType())) {
            $revision->setCseType(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        }

        $validSSE = [EncryptionService::SSE_ENCRYPTION_NONE, EncryptionService::SSE_ENCRYPTION_V1R1, EncryptionService::SSE_ENCRYPTION_V1R2, EncryptionService::SSE_ENCRYPTION_V2R1, EncryptionService::SSE_ENCRYPTION_V3R1];
        if(!in_array($revision->getSseType(), $validSSE)) {
            throw new ApiException('Invalid server side encryption type', Http::STATUS_BAD_REQUEST);
        }

        $validCSE = [EncryptionService::CSE_ENCRYPTION_NONE, EncryptionService::CSE_ENCRYPTION_V1R1];
        if(!in_array($revision->getCseType(), $validCSE)) {
            throw new ApiException('Invalid client side encryption type', Http::STATUS_BAD_REQUEST);
        }

        if($revision->getCseType() === EncryptionService::CSE_ENCRYPTION_NONE && !empty($revision->getCseKey())) {
            throw new ApiException('Invalid client side encryption type', Http::STATUS_BAD_REQUEST);
        }

        if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) {
            /** @var UserChallengeService $challengeService */
            $challengeService = $this->container->get(UserChallengeService::class);
            if(!$challengeService->hasChallenge()) {
                throw new ApiException('Invalid client side encryption type', Http::STATUS_BAD_REQUEST);
            }
        }

        if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE && empty($revision->getCseKey())) {
            throw new ApiException('Client side encryption key missing', Http::STATUS_BAD_REQUEST);
        }

        if($revision->getCseType() === EncryptionService::CSE_ENCRYPTION_NONE && $revision->getSseType() === EncryptionService::SSE_ENCRYPTION_NONE) {
            throw new ApiException('No encryption specified', Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @param PasswordRevision $password
     *
     * @throws ApiException
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    protected function checkHash(PasswordRevision $password): void {
        if(preg_match("/^[0-9a-z]{40}$/", $password->getHash())) {
            return;
        } else {
            $settingsHelper = $this->container->get(UserSettingsHelper::class);
            $length         = $settingsHelper->get('password.security.hash');

            if($password->getId() === null) {
                if($length === 0 && empty($password->getHash())) {
                    return;
                }
                if(preg_match("/^[0-9a-z]{{$length}}$/", $password->getHash()) === 1) {
                    return;
                }
            } else {
                /** @var PasswordService $passwordService */
                $passwordService = $this->container->get(PasswordService::class);
                $model           = $passwordService->findByUuid($password->getModel());

                if($model->isEditable() && empty($model->getShareId())) {
                    if($length === 0 && empty($password->getHash())) {
                        return;
                    }
                    if(preg_match("/^[0-9a-z]{{$length}}$/", $password->getHash()) === 1) {
                        return;
                    }
                } else {
                    if(empty($password->getHash()) ||
                       preg_match("/^[0-9a-z]{20}$/", $password->getHash()) === 1 ||
                       preg_match("/^[0-9a-z]{30}$/", $password->getHash()) === 1) {
                        return;
                    }
                }
            }
        }

        throw new ApiException('Field "hash" must contain a valid sha1 hash', Http::STATUS_BAD_REQUEST);
    }
}
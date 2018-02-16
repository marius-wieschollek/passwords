<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCP\AppFramework\IAppContainer;

/**
 * Class ValidationService
 *
 * @package OCA\Passwords\Services
 */
class ValidationService {

    /**
     * @var IAppContainer
     */
    protected $container;

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
        if(empty($password->getSseType())) {
            $password->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        }
        if($password->getSseType() !== EncryptionService::SSE_ENCRYPTION_V1) {
            throw new ApiException('Invalid server side encryption type', 400);
        }
        if($password->getCseType() !== EncryptionService::DEFAULT_CSE_ENCRYPTION) {
            throw new ApiException('Invalid client side encryption type', 400);
        }
        if(empty($password->getLabel())) {
            throw new ApiException('Field "label" can not be empty', 400);
        }
        if(empty($password->getHash()) || !preg_match("/^[0-9a-z]{40}$/", $password->getHash())) {
            throw new ApiException('Field "hash" must contain a valid sha1 hash', 400);
        }
        if(empty($password->getEdited()) || $password->getEdited() > strtotime('+1 hour')) {
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
        if(empty($folder->getSseType())) {
            $folder->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        }
        if($folder->getSseType() !== EncryptionService::DEFAULT_SSE_ENCRYPTION) {
            throw new ApiException('Invalid server side encryption type', 400);
        }
        if($folder->getCseType() !== EncryptionService::DEFAULT_CSE_ENCRYPTION) {
            throw new ApiException('Invalid client side encryption type', 400);
        }
        if(empty($folder->getLabel())) {
            throw new ApiException('Field "label" can not be empty', 400);
        }
        if(empty($folder->getEdited()) || $folder->getEdited() > strtotime('+1 hour')) {
            $folder->setEdited(time());
        }
        $folder->setParent(
            $this->validateFolderRelation($folder->getParent(), $folder->isHidden())
        );

        return $folder;
    }

    /**
     * @param TagRevision $tag
     *
     * @return TagRevision
     * @throws ApiException
     */
    public function validateTag(TagRevision $tag): TagRevision {
        if(empty($tag->getSseType())) {
            $tag->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        }
        if($tag->getSseType() !== EncryptionService::DEFAULT_SSE_ENCRYPTION) {
            throw new ApiException('Invalid server side encryption type', 400);
        }
        if($tag->getCseType() !== EncryptionService::DEFAULT_CSE_ENCRYPTION) {
            throw new ApiException('Invalid client side encryption type', 400);
        }
        if(empty($tag->getLabel())) {
            throw new ApiException('Field "label" can not be empty', 400);
        }
        if(empty($tag->getColor())) {
            throw new ApiException('Field "color" can not be empty', 400);
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
     * @throws \Exception
     * @throws \OCP\AppFramework\QueryException
     */
    public function validateObject(RevisionInterface $object): RevisionInterface {

        switch(get_class($object)) {
            case PasswordRevision::class:
                return $this->validatePassword($object);
            case FolderRevision::class:
                return $this->validateFolder($object);
            case TagRevision::class:
                return $this->validateTag($object);
        }

        throw new \Exception('Unknown object type');
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    public function isValidDomain(string $domain): bool {
        if(!preg_match("/^([\w_-]+\.){1,}\w+$/", $domain)) return false;
        if($domain === 'localhost') return false;
        if(!checkdnsrr($domain, 'A')) return false;

        return true;
    }

    /**
     * @param string $uuid
     *
     * @return bool
     */
    public function isValidUuid(string $uuid): bool {
        return preg_match("/^[0-9a-z]{8}\-[0-9a-z]{4}\-[0-9a-z]{4}\-[0-9a-z]{4}\-[0-9a-z]{12}$/", $uuid) != false;
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
                    $folderRevisionService = $this->container->query(FolderRevisionService::class);
                    /** @var FolderRevision $folderRevision */
                    $folderRevision = $folderRevisionService->findCurrentRevisionByModel($folderUuid, false);
                    if($folderRevision->isHidden() && !$isHidden) {
                        return FolderService::BASE_FOLDER_UUID;
                    }
                } catch(\Throwable $e) {
                    return FolderService::BASE_FOLDER_UUID;
                }
            }
        }

        return $folderUuid;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:25
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\Object\FolderService;

/**
 * Class ValidationService
 *
 * @package OCA\Passwords\Services
 */
class ValidationService {

    /**
     * @var HelperService
     */
    protected $helperService;

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * ValidationService constructor.
     *
     * @param HelperService $helperService
     * @param FolderService $folderService
     */
    public function __construct(HelperService $helperService, FolderService $folderService) {
        $this->helperService = $helperService;
        $this->folderService = $folderService;
    }

    /**
     * @param PasswordRevision $password
     *
     * @return PasswordRevision
     * @throws ApiException
     * @throws \OCP\AppFramework\QueryException
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
        if($password->getFolder() !== $this->folderService::BASE_FOLDER_UUID) {
            if(!$this->isValidUuid($password->getFolder())) {
                $password->setFolder($this->folderService::BASE_FOLDER_UUID);
            } else {
                try {
                    $this->folderService->findByUuid($password->getFolder());
                } catch(\Throwable $e) {
                    $password->setFolder($this->folderService::BASE_FOLDER_UUID);
                }
            }
        }
        if($password->getStatus() == 0) {
            $securityCheck = $this->helperService->getSecurityHelper();
            $password->setStatus($securityCheck->getRevisionSecurityLevel($password));
        }
        if(empty($password->getEdited()) || $password->getEdited() > strtotime('+1 hour')) {
            $password->setEdited(time());
        }

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
        if($folder->getParent() !== $this->folderService::BASE_FOLDER_UUID) {
            if(!$this->isValidUuid($folder->getParent())) {
                $folder->setParent($this->folderService::BASE_FOLDER_UUID);
            } else {
                try {
                    $this->folderService->findByUuid($folder->getParent());
                } catch(\Throwable $e) {
                    $folder->setParent($this->folderService::BASE_FOLDER_UUID);
                }
            }
        }
        if(empty($folder->getEdited()) || $folder->getEdited() > strtotime('+1 hour')) {
            $folder->setEdited(time());
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
}
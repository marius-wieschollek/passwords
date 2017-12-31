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
use OCA\Passwords\Db\ShareRevision;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;

/**
 * Class ValidationService
 *
 * @package OCA\Passwords\Services
 */
class ValidationService {

    /**
     * @var AbstractSecurityCheckHelper
     */
    protected $securityCheck;

    protected $passwordSseTypes = [
        EncryptionService::SSE_ENCRYPTION_V1,
        EncryptionService::SHARE_ENCRYPTION_V1
    ];

    /**
     * ValidationService constructor.
     *
     * @param AbstractSecurityCheckHelper $securityCheck
     */
    public function __construct(AbstractSecurityCheckHelper $securityCheck) {
        $this->securityCheck = $securityCheck;
    }

    /**
     * @param PasswordRevision $revision
     *
     * @return PasswordRevision
     * @throws ApiException
     */
    public function validateRevision(PasswordRevision $revision): PasswordRevision {
        if(empty($revision->getSseType())) {
            $revision->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        }
        if(!in_array($revision->getSseType(), $this->passwordSseTypes)) {
            throw new ApiException('Invalid server side encryption type', 400);
        }
        if($revision->getCseType() !== EncryptionService::DEFAULT_CSE_ENCRYPTION) {
            throw new ApiException('Invalid client side encryption type', 400);
        }
        if(empty($revision->getLabel())) {
            throw new ApiException('Field "label" can not be empty', 400);
        }
        if(empty($revision->getHash())) {
            throw new ApiException('Field "hash" can not be empty', 400);
        }
        if($revision->getStatus() == 0) {
            $revision->setStatus($this->securityCheck->getRevisionSecurityLevel($revision));
        }

        return $revision;
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

        return $tag;
    }

    /**
     * @param ShareRevision $share
     *
     * @return ShareRevision
     * @throws ApiException
     */
    public function validateShare(ShareRevision $share): ShareRevision {
        if(empty($share->getSseType())) {
            $share->setSseType(EncryptionService::DEFAULT_SHARE_ENCRYPTION);
        }
        if($share->getSseType() !== EncryptionService::DEFAULT_SHARE_ENCRYPTION) {
            throw new ApiException('Invalid server side encryption type', 400);
        }

        return $share;
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    public function isValidDomain(string $domain): bool {
        if(!preg_match("/^([\w_-]+\.){1,}\w+$/", $domain)) return false;
        if($domain == 'localhost') return false;
        if(!@get_headers('http://'.$domain)) return false;

        return true;
    }
}
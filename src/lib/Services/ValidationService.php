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
        if(empty($revision->getCseType())) {
            throw new ApiException('Field "cseType" can not be empty');
        }
        if(empty($revision->getLabel())) {
            throw new ApiException('Field "label" can not be empty');
        }
        if(empty($revision->getHash())) {
            throw new ApiException('Field "hash" can not be empty');
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
        if(empty($folder->getCseType())) {
            throw new ApiException('Field "cseType" can not be empty');
        }
        if(empty($folder->getLabel())) {
            throw new ApiException('Field "label" can not be empty');
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
        if(empty($tag->getCseType())) {
            throw new ApiException('Field "cseType" can not be empty');
        }
        if(empty($tag->getLabel())) {
            throw new ApiException('Field "label" can not be empty');
        }
        if(empty($tag->getColor())) {
            throw new ApiException('Field "color" can not be empty');
        }

        return $tag;
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
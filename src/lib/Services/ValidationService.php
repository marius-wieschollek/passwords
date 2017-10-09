<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:25
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\Revision;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Exception\ApiException;

/**
 * Class ValidationService
 *
 * @package OCA\Passwords\Services
 */
class ValidationService {

    /**
     * @param Revision $revision
     *
     * @return Revision
     * @throws ApiException
     */
    public function validateRevision(Revision $revision): Revision {
        if(empty($revision->getSseType())) {
            $revision->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        }
        if(empty($revision->getCseType())) {
            throw new ApiException('Field "cseType" can not be empty');
        }
        if(empty($revision->getTitle())) {
            throw new ApiException('Field "title" can not be empty');
        }
        if(empty($revision->getHash())) {
            throw new ApiException('Field "hash" can not be empty');
        }

        return $revision;
    }

    /**
     * @param Folder $folder
     *
     * @return Folder
     * @throws ApiException
     */
    public function validateFolder(Folder $folder): Folder {
        if(empty($folder->getSseType())) {
            $folder->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        }
        if(empty($folder->getCseType())) {
            throw new ApiException('Field "cseType" can not be empty');
        }
        if(empty($folder->getName())) {
            throw new ApiException('Field "name" can not be empty');
        }

        return $folder;
    }

    /**
     * @param Tag $tag
     *
     * @return Tag
     * @throws ApiException
     */
    public function validateTag(Tag $tag): Tag {
        if(empty($tag->getSseType())) {
            $tag->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        }
        if(empty($tag->getCseType())) {
            throw new ApiException('Field "cseType" can not be empty');
        }
        if(empty($tag->getName())) {
            throw new ApiException('Field "name" can not be empty');
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
<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:25
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\Revision;
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
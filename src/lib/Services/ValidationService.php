<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:25
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\Revision;

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

        if(empty($revision->getTitle())) {
            $title = $revision->getLogin();
            if(!empty($revision->getUrl())) $title .= '@'.parse_url($revision->getUrl(), PHP_URL_HOST);
            $revision->setTitle($title);
        }

        if(empty($revision->getSseType())) {
            $revision->setSseType(\OCA\Passwords\Services\EncryptionService::DEFAULT_SSE_ENCRYPTION);
        }
        if(empty($revision->getCseType())) {
            $revision->setCseType(\OCA\Passwords\Services\EncryptionService::DEFAULT_CSE_ENCRYPTION);
        }

        return $revision;
    }
}
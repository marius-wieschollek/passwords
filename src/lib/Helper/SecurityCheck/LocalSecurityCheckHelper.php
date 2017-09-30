<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 16.09.17
 * Time: 22:39
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use OCA\Passwords\Db\Revision;

class LocalSecurityCheckHelper extends AbstractSecurityCheckHelper {

    /**
     * @param Revision $revision
     *
     * @return int
     */
    public function getPasswordSecurityLevel(Revision $revision): int {

        return parent::getPasswordSecurityLevel($revision);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 16.09.17
 * Time: 22:22
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use OCA\Passwords\Db\Revision;

class HbipOnlineHelper extends AbstractSecurityCheckHelper {

    /**
     * @param Revision $revision
     *
     * @return int
     */
    public function getPasswordSecurityLevel(Revision $revision): int {

        return parent::getPasswordSecurityLevel($revision);
    }
}
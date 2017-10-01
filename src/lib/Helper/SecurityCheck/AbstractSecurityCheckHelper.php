<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 16.09.17
 * Time: 22:31
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use OCA\Passwords\Db\Revision;

/**
 * Class AbstractSecurityCheckHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
abstract class AbstractSecurityCheckHelper {

    /**
     * @param Revision $revision
     *
     * @return int
     */
    public function getPasswordSecurityLevel(Revision $revision): int {

        return 0;
    }
}
<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db\Legacy;

use OCP\AppFramework\Db\Entity;

/**
 * Class LegacyShare
 *
 * @package OCA\Passwords\Db\Legacy
 *
 * @method getPwid()
 * @method getSharedto()
 * @method getSharekey()
 */
class LegacyShare extends Entity {

    protected $pwid;
    protected $sharedto;
    protected $sharekey;

}
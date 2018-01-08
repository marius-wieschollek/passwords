<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 27.12.17
 * Time: 22:41
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
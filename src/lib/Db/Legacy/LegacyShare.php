<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 27.12.17
 * Time: 22:41
 */

namespace OCA\Passwords\Db\Legacy;

use OCP\AppFramework\Db\Entity;

class LegacyShare extends Entity {

    public $id;
    protected $pwid;
    protected $sharedto;
    protected $sharekey;

}
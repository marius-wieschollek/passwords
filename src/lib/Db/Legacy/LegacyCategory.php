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
 * Class LegacyCategory
 *
 * @package OCA\Passwords\Db\Legacy
 *
 * @method string getId()
 * @method string getUserId()
 * @method string getCategoryName()
 * @method string getCategoryColour()
 */
class LegacyCategory extends Entity {

    public    $id;
    protected $userId;
    protected $categoryName;
    protected $categoryColour;

}
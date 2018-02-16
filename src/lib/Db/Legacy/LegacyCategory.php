<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db\Legacy;

use OCP\AppFramework\Db\Entity;

/**
 * Class LegacyCategory
 *
 * @package OCA\Passwords\Db\Legacy
 *
 * @method string getUserId()
 * @method string getCategoryName()
 * @method string getCategoryColour()
 */
class LegacyCategory extends Entity {

    protected $userId;
    protected $categoryName;
    protected $categoryColour;

}
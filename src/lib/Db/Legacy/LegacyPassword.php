<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 27.12.17
 * Time: 22:38
 */

namespace OCA\Passwords\Db\Legacy;

use OCP\AppFramework\Db\Entity;

/**
 * Class LegacyPassword
 *
 * @package OCA\Passwords\Db
 *
 * @method string getPass()
 * @method string getNotes()
 * @method string getUserId()
 * @method string getWebsite()
 * @method string getProperties()
 */
class LegacyPassword extends Entity {

    protected $userId;
    protected $website;
    protected $pass;
    protected $properties;

    /**
     * Not used
     */
    protected $notes;
    protected $address;
    protected $deleted;
    protected $loginname;
    protected $creationDate;

}
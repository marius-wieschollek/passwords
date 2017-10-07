<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 07.10.17
 * Time: 16:47
 */

namespace OCA\Passwords\Db;

/**
 * Class PasswordTagRelation
 *
 * @package OCA\Passwords\Db
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getUser()
 * @method void setUser(string $user)
 * @method string getTag()
 * @method void setTag(string $tag)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method bool getDeleted()
 * @method void setDeleted(bool $deleted)
 * @method int getCreated()
 * @method void setCreated(int $created)
 * @method int getUpdated()
 * @method void setUpdated(int $updated)
 */
class PasswordTagRelation extends AbstractEntity {

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $tag;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var bool
     */
    protected $deleted;

    /**
     * @var int
     */
    protected $created;

    /**
     * @var int
     */
    protected $updated;

    /**
     * PasswordTagRelation constructor.
     */
    public function __construct() {
        $this->addType('tag', 'string');
        $this->addType('user', 'string');
        $this->addType('password', 'string');
        $this->addType('deleted', 'boolean');
        $this->addType('created', 'integer');
        $this->addType('updated', 'integer');
    }
}
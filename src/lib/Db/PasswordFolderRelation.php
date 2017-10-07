<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 07.10.17
 * Time: 16:41
 */

namespace OCA\Passwords\Db;

/**
 * Class PasswordFolderRelation
 *
 * @package OCA\Passwords\Db
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getUser()
 * @method void setUser(string $user)
 * @method string getFolder()
 * @method void setFolder(string $folder)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method bool getDeleted()
 * @method void setDeleted(bool $deleted)
 * @method int getCreated()
 * @method void setCreated(int $created)
 * @method int getUpdated()
 * @method void setUpdated(int $updated)
 */
class PasswordFolderRelation extends AbstractEntity {

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $folder;

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
     * PasswordFolderRelation constructor.
     */
    public function __construct() {
        $this->addType('user', 'string');
        $this->addType('folder', 'string');
        $this->addType('password', 'string');
        $this->addType('deleted', 'boolean');
        $this->addType('created', 'integer');
        $this->addType('updated', 'integer');
    }
}
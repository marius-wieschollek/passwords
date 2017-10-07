<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 07.10.17
 * Time: 16:48
 */

namespace OCA\Passwords\Db;

/**
 * Class FolderFolderRelation
 *
 * @package OCA\Passwords\Db
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getUser()
 * @method void setUser(string $user)
 * @method string getParent()
 * @method void setParent(string $parent)
 * @method string getChild()
 * @method void setChild(string $child)
 * @method bool getDeleted()
 * @method void setDeleted(bool $deleted)
 * @method int getCreated()
 * @method void setCreated(int $created)
 * @method int getUpdated()
 * @method void setUpdated(int $updated)
 */
class FolderFolderRelation extends AbstractEntity {

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $parent;

    /**
     * @var string
     */
    protected $child;

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
     * FolderFolderRelation constructor.
     */
    public function __construct() {
        $this->addType('user', 'string');
        $this->addType('child', 'string');
        $this->addType('parent', 'string');
        $this->addType('deleted', 'boolean');
        $this->addType('created', 'integer');
        $this->addType('updated', 'integer');
    }
}
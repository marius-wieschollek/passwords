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
 * @method string getParent()
 * @method void setParent(string $parent)
 * @method string getChild()
 * @method void setChild(string $child)
 */
class FolderFolderRelation extends AbstractEntity {

    /**
     * @var string
     */
    protected $parent;

    /**
     * @var string
     */
    protected $child;

    /**
     * FolderFolderRelation constructor.
     */
    public function __construct() {
        $this->addType('child', 'string');
        $this->addType('parent', 'string');

        parent::__construct();
    }
}
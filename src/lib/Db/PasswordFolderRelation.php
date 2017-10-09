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
 * @method string getFolder()
 * @method void setFolder(string $folder)
 * @method string getPassword()
 * @method void setPassword(string $password)
 */
class PasswordFolderRelation extends AbstractEntity {

    /**
     * @var string
     */
    protected $folder;

    /**
     * @var string
     */
    protected $password;

    /**
     * PasswordFolderRelation constructor.
     */
    public function __construct() {
        $this->addType('folder', 'string');
        $this->addType('password', 'string');

        parent::__construct();
    }
}
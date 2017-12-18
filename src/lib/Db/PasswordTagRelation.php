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
 * @method string getTag()
 * @method void setTag(string $tag)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method string getTagRevision()
 * @method void setTagRevision(string $tagRevision)
 * @method string getPasswordRevision()
 * @method void setPasswordRevision(string $passwordRevision)
 */
class PasswordTagRelation extends AbstractEntity {

    /**
     * @var string
     */
    protected $tag;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $tagRevision;

    /**
     * @var string
     */
    protected $passwordRevision;

    /**
     * PasswordTagRelation constructor.
     */
    public function __construct() {
        $this->addType('tag', 'string');
        $this->addType('password', 'string');
        $this->addType('tagRevision', 'string');
        $this->addType('passwordRevision', 'string');

        parent::__construct();
    }
}
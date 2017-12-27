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
 * @method bool getHidden()
 * @method void setHidden(bool $hidden)
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
     * @var bool
     */
    protected $hidden;

    /**
     * PasswordTagRelation constructor.
     */
    public function __construct() {
        $this->addType('tag', 'string');
        $this->addType('password', 'string');
        $this->addType('tagRevision', 'string');
        $this->addType('passwordRevision', 'string');

        $this->addType('hidden', 'boolean');

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function isHidden(): bool {
        return $this->getHidden();
    }
}
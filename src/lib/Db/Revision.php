<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.08.17
 * Time: 21:33
 */

namespace OCA\Passwords\Db;

use JsonSerializable;

/**
 * Class Revision
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method bool getDeleted()
 * @method void setDeleted(bool $deleted)
 * @method bool getHidden()
 * @method void setHidden(bool $hidden)
 * @method string getUser()
 * @method void setUser(string $user)
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getSseType()
 * @method void setSseType(string $sseType)
 * @method string getCseType()
 * @method void setCseType(string $cseType)
 * @method string getKey()
 * @method void setKey(string $key)
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string getUrl()
 * @method void setUrl(string $url)
 * @method string getLogin()
 * @method void setLogin(string $login)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method string getNotes()
 * @method void setNotes(string $notes)
 * @method string getHash()
 * @method void setHash(string $hash)
 * @method bool getFavourite()
 * @method void setFavourite(bool $favourite)
 * @method int getStatus()
 * @method void setStatus(int $status)
 * @method int getPasswordId()
 * @method void setPasswordId(int $passwordId)
 * @method int getCreated()
 * @method void setCreated(int $created)
 * @method int getUpdated()
 * @method void setUpdated(int $updated)
 *
 * @package OCA\Passwords\Db
 */
class Revision extends AbstractEntity implements JsonSerializable {

    /**
     * @var string
     */
    protected $user;

    /**
     * @var
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $sseType;

    /**
     * @var string
     */
    protected $cseType;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $login;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $notes;

    /**
     * @var string
     */
    protected $hash;

    /**
     * @var bool
     */
    protected $deleted;

    /**
     * @var bool
     */
    protected $hidden;

    /**
     * @var bool
     */
    protected $favourite;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var int
     */
    protected $passwordId;

    /**
     * @var int
     */
    protected $created;

    /**
     * @var int
     */
    protected $updated;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('key', 'string');
        $this->addType('url', 'string');
        $this->addType('hash', 'string');
        $this->addType('user', 'string');
        $this->addType('uuid', 'string');
        $this->addType('login', 'string');
        $this->addType('notes', 'string');
        $this->addType('title', 'string');
        $this->addType('sseType', 'string');
        $this->addType('cseType', 'string');
        $this->addType('password', 'string');

        $this->addType('hidden', 'boolean');
        $this->addType('deleted', 'boolean');
        $this->addType('favourite', 'boolean');

        $this->addType('status', 'integer');
        $this->addType('created', 'integer');
        $this->addType('updated', 'integer');
        $this->addType('passwordId', 'integer');
    }

    /**
     * @return bool
     */
    public function isHidden(): bool {
        return $this->getHidden();
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool {
        return $this->getDeleted();
    }

    /**
     * @return bool
     */
    public function isFavourite(): bool {
        return $this->getFavourite();
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize() {
        return [
            'id'         => $this->getId(),
            'url'        => $this->getUrl(),
            'hash'       => $this->getHash(),
            'user'       => $this->getUser(),
            'uuid'       => $this->getUuid(),
            'login'      => $this->getLogin(),
            'notes'      => $this->getNotes(),
            'title'      => $this->getTitle(),
            'cseType'    => $this->getCseType(),
            'sseType'    => $this->getSseType(),
            'password'   => $this->getPassword(),
            'hidden'     => $this->isHidden(),
            'deleted'    => $this->isDeleted(),
            'favourite'  => $this->isFavourite(),
            'status'     => $this->getStatus(),
            'passwordId' => $this->getPasswordId(),
            'created'    => $this->getCreated(),
            'updated'    => $this->getUpdated(),
        ];
    }
}
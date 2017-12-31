<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 14:14
 */

namespace OCA\Passwords\Db;

/**
 * Class ShareRevision
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getSseKey()
 * @method void setSseKey(string $sseKey)
 * @method string getSseType()
 * @method void setSseType(string $sseType)
 * @method string getCseType()
 * @method void setCseType(string $cseType)
 * @method string getModel()
 * @method void setModel(string $model)
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method string getClient()
 * @method void setClient(string $client)
 * @method string getUrl()
 * @method void setUrl(string $url)
 * @method string getUsername()
 * @method void setUsername(string $username)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method string getNotes()
 * @method void setNotes(string $notes)
 * @method string getHash()
 * @method void setHash(string $hash)
 * @method bool getEditable()
 * @method void setEditable(bool $editable)
 * @method bool getSynchronized()
 * @method void setSynchronized(bool $synchronized)
 *
 * @package OCA\Passwords\Db
 */
class ShareRevision extends AbstractEntity implements RevisionInterface {

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $sseKey;

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
    protected $model;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $client;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $username;

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
    protected $editable;

    /**
     * @var bool
     */
    protected $synchronized;

    /**
     * @var bool
     */
    protected $_decrypted = false;

    /**
     * AbstractRevisionEntity constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->addType('sseType', 'string');
        $this->addType('sseKey', 'string');
        $this->addType('cseType', 'string');
        $this->addType('uuid', 'string');
        $this->addType('model', 'string');
        $this->addType('client', 'string');

        $this->addType('url', 'string');
        $this->addType('hash', 'string');
        $this->addType('label', 'string');
        $this->addType('notes', 'string');
        $this->addType('username', 'string');
        $this->addType('password', 'string');

        $this->addType('editable', 'boolean');
        $this->addType('synchronized', 'boolean');
    }

    /**
     * @return bool
     */
    public function isEditable(): bool {
        return $this->getEditable() === true;
    }

    /**
     * @return bool
     */
    public function isSynchronized(): bool {
        return $this->getSynchronized() === true;
    }

    /**
     * @return bool
     */
    public function _isDecrypted(): bool {
        return $this->_decrypted === true;
    }

    /**
     * @param bool $decrypted
     */
    public function _setDecrypted(bool $decrypted) {
        $this->_decrypted = $decrypted === true;
    }
}
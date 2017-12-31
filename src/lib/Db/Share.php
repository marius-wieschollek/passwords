<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 14:14
 */

namespace OCA\Passwords\Db;

/**
 * Class Share
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getRevision()
 * @method void setRevision(string $revision)
 * @method string getPasswordId()
 * @method void setPasswordId(string $passwordId)
 * @method string getReceiverId()
 * @method void setReceiverId(string $receiverId)
 *
 * @package OCA\Passwords\Db
 */
class Share extends AbstractEntity implements ModelInterface {

    const TYPE_USER  = 'user';
    const TYPE_GROUP = 'group';
    const TYPE_LINK  = 'link';

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $revision;

    /**
     * @var string
     */
    protected $passwordId;

    /**
     * @var string
     */
    protected $receiverId;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('uuid', 'string');
        $this->addType('type', 'string');
        $this->addType('revision', 'string');
        $this->addType('passwordId', 'string');
        $this->addType('receiverId', 'string');

        parent::__construct();
    }
}
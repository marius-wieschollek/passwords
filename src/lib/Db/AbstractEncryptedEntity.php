<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 19:06
 */

namespace OCA\Passwords\Db;

/**
 * Class AbstractEncryptedEntity
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getSseKey()
 * @method void setSseKey(string $sseKey)
 * @method string getSseType()
 * @method void setSseType(string $sseType)
 * @method string getCseType()
 * @method void setCseType(string $cseType)
 * @method bool getHidden()
 * @method void setHidden(bool $hidden)
 * @method bool getTrashed()
 * @method void setTrashed(bool $trashed)
 * @method bool getFavourite()
 * @method void setFavourite(bool $favourite)
 *
 * @package OCA\Passwords\Db
 */
class AbstractEncryptedEntity extends AbstractEntity {

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
     * @var bool
     */
    protected $hidden;


    /**
     * @var bool
     */
    protected $trashed;

    /**
     * @var bool
     */
    protected $favourite;

    /**
     * AbstractEncryptedEntity constructor.
     */
    public function __construct() {
        $this->addType('sseType', 'string');
        $this->addType('sseKey', 'string');
        $this->addType('cseType', 'string');

        $this->addType('uuid', 'string');
        $this->addType('hidden', 'boolean');
        $this->addType('trashed', 'boolean');
        $this->addType('favourite', 'boolean');

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function isTrashed(): bool {
        return $this->getTrashed();
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
    public function isFavourite(): bool {
        return $this->getHidden();
    }
}
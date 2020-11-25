<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class AbstractRevision
 *
 * @method string|null getSseKey()
 * @method void setSseKey(string|null $sseKey)
 * @method string getSseType()
 * @method void setSseType(string $sseType)
 * @method string getCseType()
 * @method void setCseType(string $cseType)
 * @method string getCseKey()
 * @method void setCseKey(string $cseKey)
 * @method string getModel()
 * @method void setModel(string $model)
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method int getEdited()
 * @method void setEdited(int $edited)
 * @method bool getHidden()
 * @method void setHidden(bool $hidden)
 * @method bool getTrashed()
 * @method void setTrashed(bool $trashed)
 * @method bool getFavorite()
 * @method void setFavorite(bool $favorite)
 * @method string getClient()
 * @method void setClient(string $client)
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractRevision extends AbstractEntity implements RevisionInterface {

    /**
     * @var string|null
     */
    protected ?string $sseKey;

    /**
     * @var string
     */
    protected string $sseType;

    /**
     * @var string
     */
    protected string $cseType;

    /**
     * @var string|null
     */
    protected ?string $cseKey;

    /**
     * @var string
     */
    protected string $model;

    /**
     * @var string
     */
    protected string $label;

    /**
     * @var int
     */
    protected int $edited;

    /**
     * @var bool
     */
    protected bool $hidden;

    /**
     * @var bool
     */
    protected bool $trashed;

    /**
     * @var bool
     */
    protected bool $favorite;

    /**
     * @var string|null
     */
    protected ?string $client;

    /**
     * @var bool
     */
    protected bool $_decrypted = false;

    /**
     * AbstractRevision constructor.
     */
    public function __construct() {
        $this->addType('sseType', 'string');
        $this->addType('sseKey', 'string');
        $this->addType('cseType', 'string');
        $this->addType('cseKey', 'string');

        $this->addType('model', 'string');
        $this->addType('label', 'string');
        $this->addType('client', 'string');
        $this->addType('edited', 'integer');
        $this->addType('hidden', 'boolean');
        $this->addType('trashed', 'boolean');
        $this->addType('favorite', 'boolean');

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
    public function isFavorite(): bool {
        return $this->getFavorite();
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
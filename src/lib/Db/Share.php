<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class Share
 *
 * @method string getType()
 * @method void setType(string $type)
 * @method string getSourcePassword()
 * @method void setSourcePassword(string $sourcePassword)
 * @method string getTargetPassword()
 * @method void setTargetPassword(string $targetPassword)
 * @method string getReceiver()
 * @method void setReceiver(string $receiver)
 * @method bool getEditable()
 * @method void setEditable(bool $editable)
 * @method bool getShareable()
 * @method void setShareable(bool $shareable)
 * @method bool getSourceUpdated()
 * @method bool getTargetUpdated()
 * @method int|null getExpires()
 * @method void setExpires(int|null $expires)
 * @method string getClient()
 * @method void setClient(string $client)
 *
 * @package OCA\Passwords\Db
 */
class Share extends AbstractEntity implements EntityInterface {

    const TYPE_USER  = 'user';
    const TYPE_GROUP = 'group';
    const TYPE_LINK  = 'link';

    /**
     * @var string
     */
    protected string $type;

    /**
     * @var string
     */
    protected string $client;

    /**
     * @var string
     */
    protected string $receiver;

    /**
     * @var bool
     */
    protected bool $editable;

    /**
     * @var bool
     */
    protected bool $shareable;

    /**
     * @var int|null
     */
    protected ?int $expires;

    /**
     * @var string
     */
    protected string $sourcePassword;

    /**
     * @var string|null
     */
    protected ?string $targetPassword;

    /**
     * @var bool
     */
    protected bool $sourceUpdated;

    /**
     * @var bool
     */
    protected bool $targetUpdated;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('type', 'string');
        $this->addType('client', 'string');
        $this->addType('receiver', 'string');
        $this->addType('sourcePassword', 'string');
        $this->addType('targetPassword', 'string');

        $this->addType('expires', 'integer');

        $this->addType('editable', 'boolean');
        $this->addType('shareable', 'boolean');
        $this->addType('sourceUpdated', 'boolean');
        $this->addType('targetUpdated', 'boolean');

        parent::__construct();
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
    public function isShareable(): bool {
        return $this->getShareable() === true;
    }

    /**
     * @return bool
     */
    public function isSourceUpdated(): bool {
        return $this->getSourceUpdated() === true;
    }

    /**
     * @param bool $sourceUpdated
     */
    public function setSourceUpdated(bool $sourceUpdated): void {
        $this->sourceUpdated = $sourceUpdated === true;
        $this->markFieldUpdated('sourceUpdated');
    }

    /**
     * @return bool
     */
    public function isTargetUpdated(): bool {
        return $this->getTargetUpdated() === true;
    }

    /**
     * @param bool $targetUpdated
     */
    public function setTargetUpdated(bool $targetUpdated): void {
        $this->targetUpdated = $targetUpdated === true;
        $this->markFieldUpdated('targetUpdated');
    }
}
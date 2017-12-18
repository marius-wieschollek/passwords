<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 14:34
 */

namespace OCA\Passwords\Db;

/**
 * Class TagRevision
 *
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method string getColor()
 * @method void setColor(string $color)
 *
 * @package OCA\Passwords\Db
 */
class TagRevision extends AbstractEncryptedEntity {

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $color;

    /**
     * TagRevision constructor.
     */
    public function __construct() {
        $this->addType('name', 'string');
        $this->addType('color', 'string');

        parent::__construct();
    }
}
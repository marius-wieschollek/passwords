<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 14:34
 */

namespace OCA\Passwords\Db;

/**
 * Class Tag
 *
 * @method string getName()
 * @method void setName(string $name)
 * @method string getColor()
 * @method void setColor(string $color)
 *
 * @package OCA\Passwords\Db
 */
class Tag extends AbstractEncryptedEntity {

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $color;

    /**
     * Tag constructor.
     */
    public function __construct() {
        $this->addType('name', 'string');
        $this->addType('color', 'string');

        parent::__construct();
    }
}
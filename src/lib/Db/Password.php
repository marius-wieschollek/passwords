<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 20:35
 */

namespace OCA\Passwords\Db;

/**
 * Class Password
 *
 * @package OCA\Passwords\Db
 *
 * @method string|null getShareId()
 * @method void setShareId(string $shareId)
 * @method bool getEditable()
 * @method void setEditable(bool $editable)
 */
class Password extends AbstractModelEntity {

    /**
     * @var string
     */
    protected $shareId;

    /**
     * @var bool
     */
    protected $editable;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('shareId', 'string');
        $this->addType('writable', 'boolean');

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function isEditable(): bool {
        return $this->getEditable();
    }
}
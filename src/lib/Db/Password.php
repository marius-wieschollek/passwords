<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 20:35
 */

namespace OCA\Passwords\Db;

use JsonSerializable;

/**
 * Class Password
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getRevision()
 * @method void setRevision(string $revision)
 *
 * @package OCA\Passwords\Db
 */
class Password extends AbstractEntity {

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $revision;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('uuid', 'string');
        $this->addType('revision', 'string');

        parent::__construct();
    }
}
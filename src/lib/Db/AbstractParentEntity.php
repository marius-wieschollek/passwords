<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 17:11
 */

namespace OCA\Passwords\Db;

/**
 * Class AbstractParentEntity
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getRevision()
 * @method void setRevision(string $revision)
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractParentEntity extends AbstractEntity {

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
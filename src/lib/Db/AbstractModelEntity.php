<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class AbstractModelEntity
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getRevision()
 * @method void setRevision(string $revision)
 * @method bool getSuspended()
 * @method void setSuspended(bool $suspended)
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractModelEntity extends AbstractEntity implements ModelInterface {

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $revision;

    /**
     * @var string
     */
    protected $suspended;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('uuid', 'string');
        $this->addType('revision', 'string');
        $this->addType('suspended', 'boolean');

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function isSuspended(): bool {
        return $this->getSuspended() === true;
    }
}
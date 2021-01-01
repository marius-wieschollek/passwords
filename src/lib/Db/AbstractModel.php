<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class AbstractModel
 *
 * @method string getRevision()
 * @method void setRevision(string $revision)
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractModel extends AbstractEntity implements ModelInterface {

    /**
     * @var string
     */
    protected string $revision;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('revision', 'string');

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function isSuspended(): bool {
        return false;
    }
}
<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class TagRevision
 *
 * @method string getColor()
 * @method void setColor(string $color)
 *
 * @package OCA\Passwords\Db
 */
class TagRevision extends AbstractRevision {

    /**
     * @var string
     */
    protected $color;

    /**
     * TagRevision constructor.
     */
    public function __construct() {
        $this->addType('label', 'string');
        $this->addType('color', 'string');

        parent::__construct();
    }
}
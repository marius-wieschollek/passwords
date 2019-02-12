<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class Keychain
 *
 * @method string getData()
 * @method void setData(string $data)
 * @method string getName()
 * @method void setName(string $name)
 *
 * @package OCA\Passwords\Db
 */
class Keychain extends AbstractEntity {

    /**
     * @var string
     */
    protected $data;

    /**
     * @var string
     */
    protected $name;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('name', 'string');
        $this->addType('data', 'string');

        parent::__construct();
    }
}
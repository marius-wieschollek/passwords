<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class Registration
 * 
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getCode()
 * @method void setCode(string $code)
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method string getLogin()
 * @method void setLogin(string $login)
 * @method string getToken()
 * @method void setToken(string $token)
 * @method int getStatus()
 * @method void setStatus(int $status)
 * @method int getLimit()
 * @method void setLimit(int $limit)
 *
 * @package OCA\Passwords\Db
 */
class Registration extends AbstractEntity {

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $login;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var int
     */
    protected $limit;

    /**
     * Registration constructor.
     */
    public function __construct() {
        $this->addType('code', 'string');
        $this->addType('label', 'string');
        $this->addType('login', 'string');
        $this->addType('token', 'string');
        $this->addType('limit', 'int');
        $this->addType('status', 'int');

        parent::__construct();
    }
}
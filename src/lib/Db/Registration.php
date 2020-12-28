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
 * @method string|null getCode()
 * @method void setCode(string $code)
 * @method string|null getLabel()
 * @method void setLabel(string $label)
 * @method string|null getLogin()
 * @method void setLogin(string $login)
 * @method string|null getToken()
 * @method void setToken(string $token)
 * @method int getStatus()
 * @method void setStatus(int $status)
 * @method int|null getLimit()
 * @method void setLimit(int $limit)
 *
 * @package OCA\Passwords\Db
 */
class Registration extends AbstractEntity {

    /**
     * @var string|null
     */
    protected ?string $label;

    /**
     * @var string|null
     */
    protected ?string $code;

    /**
     * @var string|null
     */
    protected ?string $login;

    /**
     * @var string|null
     */
    protected ?string $token;

    /**
     * @var int
     */
    protected int $status;

    /**
     * @var int|null
     */
    protected ?int $limit;

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
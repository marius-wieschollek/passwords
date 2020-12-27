<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class Session
 *
 * @method string getData()
 * @method void setData(string $data)
 * @method string getShadowData()
 * @method void setShadowData(string $shadowData)
 * @method bool getAuthorized()
 * @method void setAuthorized(bool $authorized)
 * @method string getClient()
 * @method void setClient(string $client)
 * @method string getLoginType()
 * @method void setLoginType(string $loginType)
 *
 * @package OCA\Passwords\Db
 */
class Session extends AbstractEntity {

    /**
     * @var string
     */
    protected string $data;

    /**
     * @var string
     */
    protected string $client;

    /**
     * @var boolean
     */
    protected bool $authorized;

    /**
     * @var string
     */
    protected string $shadowData;

    /**
     * @var string
     */
    protected string $loginType;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('data', 'string');
        $this->addType('client', 'string');
        $this->addType('loginType', 'string');
        $this->addType('shadowData', 'string');
        $this->addType('authorized', 'boolean');

        parent::__construct();
    }
}
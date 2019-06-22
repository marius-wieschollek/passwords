<?php

namespace OCA\Passwords\Db;

/**
 * Class Challenge
 * @method string getClientData()
 * @method void setClientData(string $clientData)
 * @method string getServerData()
 * @method void setServerData(string $serverData)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getSecret()
 * @method void setSecret(string $secret)
 *
 * @package OCA\Passwords\Db
 */
class Challenge extends AbstractEntity {

    const TYPE_PWD_V1R1 = 'PWDv1r1';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $clientData;

    /**
     * @var string
     */
    protected $serverData;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var bool
     */
    protected $_decrypted = false;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('type', 'string');
        $this->addType('secret', 'string');
        $this->addType('clientData', 'string');
        $this->addType('serverData', 'string');

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function _isDecrypted(): bool {
        return $this->_decrypted === true;
    }

    /**
     * @param bool $decrypted
     */
    public function _setDecrypted(bool $decrypted) {
        $this->_decrypted = $decrypted === true;
    }

}
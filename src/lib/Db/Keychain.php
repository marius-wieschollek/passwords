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
 * @method string|array getData()
 * @method void setData(string|array $data)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getScope()
 * @method void setScope(string $scope)
 *
 * @package OCA\Passwords\Db
 */
class Keychain extends AbstractEntity {

    const SCOPE_CLIENT = 'client';
    const SCOPE_SERVER = 'server';

    const TYPE_CSE_V1V1 = 'CSEv1r1';
    const TYPE_SSE_V2R1 = 'SSEv2r1';

    /**
     * @var string
     */
    protected $data;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var bool
     */
    protected $_decrypted = false;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('data', 'string');
        $this->addType('type', 'string');
        $this->addType('scope', 'string');

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
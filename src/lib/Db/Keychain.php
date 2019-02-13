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
     * @return array
     */
    public function getDataArray(): array {
        $data = $this->getData();

        if(empty($data)) return [];

        return json_decode($data, true);
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function setDataArray(array $data): void {
        $this->setData(json_encode($data));
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
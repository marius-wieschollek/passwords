<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class Session
 *
 * @method string getData()
 * @method void setData(string $data)
 * @method string getShadowData()
 * @method void setShadowData(string $data)
 * @method bool getAuthorized()
 * @method void setAuthorized(bool $authorized)
 *
 * @package OCA\Passwords\Db
 */
class Session extends AbstractEntity {

    /**
     * @var string
     */
    protected $data;

    /**
     * @var boolean
     */
    protected $authorized;

    /**
     * @var string
     */
    protected $shadowData;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('data', 'string');
        $this->addType('shadowData', 'string');
        $this->addType('authorized', 'boolean');

        parent::__construct();
    }
}
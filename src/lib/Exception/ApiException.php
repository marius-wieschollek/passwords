<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 14:02
 */

namespace OCA\Passwords\Exception;

/**
 * Class ApiException
 *
 * @package OCA\Passwords\Exception
 */
class ApiException extends \Exception {

    /**
     * ApiException constructor.
     *
     * @param string $message
     */
    public function __construct($message = "") {
        parent::__construct($message, E_USER_ERROR, null);
    }

    /**
     * @return int
     */
    public function getHttpCode() {
        return 500;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return hash('crc32', $this->getMessage());
    }
}
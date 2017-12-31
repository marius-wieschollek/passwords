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
     * @var int
     */
    private $httpCode;

    /**
     * ApiException constructor.
     *
     * @param string $message
     * @param int    $httpCode
     */
    public function __construct($message = "", $httpCode = 500) {
        parent::__construct($message, E_USER_ERROR, null);
        $this->httpCode = $httpCode;
    }

    /**
     * @return int
     */
    public function getHttpCode() {
        return $this->httpCode;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return hash('crc32', $this->getMessage());
    }
}
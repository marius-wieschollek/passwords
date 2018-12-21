<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
     * @param string          $message
     * @param int             $httpCode
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", $httpCode = 500, \Throwable $previous = null) {
        parent::__construct($message, E_USER_ERROR, $previous);
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
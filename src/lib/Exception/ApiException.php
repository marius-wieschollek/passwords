<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Exception;

use Exception;
use Throwable;

/**
 * Class ApiException
 *
 * @package OCA\Passwords\Exception
 */
class ApiException extends Exception {

    /**
     * ApiException constructor.
     *
     * @param string         $message
     * @param int            $httpCode
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", private int $httpCode = 500, ?Throwable $previous = null) {
        parent::__construct($message, E_USER_ERROR, $previous);
    }

    /**
     * @return int
     */
    public function getHttpCode(): int {
        return $this->httpCode;
    }

    /**
     * @return string
     */
    public function getId(): string {
        return hash('crc32', $this->getMessage());
    }
}

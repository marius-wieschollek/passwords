<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Exception\Favicon;

/**
 * Class UnexpectedResponseCodeException
 *
 * @package OCA\Passwords\Exception\Favicon
 */
class UnexpectedResponseCodeException extends \Exception {
    const EXCEPTION_CODE = 102;

    public function __construct($responseCode, \Throwable $previous = null) {
        parent::__construct('Favicon service returned unexpected HTTP Response code '.$responseCode, static::EXCEPTION_CODE, $previous);
    }
}
<?php
/*
 * @copyright 2020 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Exception\SecurityCheck;

use Exception;
use Throwable;

/**
 * Class BreachedPasswordsZipAccessException
 *
 * @package OCA\Passwords\Exception\SecurityCheck
 */
class BreachedPasswordsZipAccessException extends Exception {
    const EXCEPTION_CODE = 108;

    public function __construct($errorCode, Throwable $previous = null) {
        parent::__construct('Unable to read breached passwords zip file. Error '.$errorCode, static::EXCEPTION_CODE, $previous);
    }
}
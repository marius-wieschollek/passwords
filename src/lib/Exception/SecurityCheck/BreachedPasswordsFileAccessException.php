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
 * Class BreachedPasswordsFileAccessException
 *
 * @package OCA\Passwords\Exception\SecurityCheck
 */
class BreachedPasswordsFileAccessException extends Exception {
    const EXCEPTION_CODE = 107;

    public function __construct(string $fileName, Throwable $previous = null) {
        parent::__construct('Unable to open or read breached passwords file '.$fileName, static::EXCEPTION_CODE, $previous);
    }
}
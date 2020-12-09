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
 * Class BreachedPasswordsZipExtractException
 *
 * @package OCA\Passwords\Exception\SecurityCheck
 */
class BreachedPasswordsZipExtractException extends Exception {
    const EXCEPTION_CODE = 109;

    public function __construct(string $file, Throwable $previous = null) {
        parent::__construct("Unable to extract {$file} from breached passwords zip file", static::EXCEPTION_CODE, $previous);
    }
}
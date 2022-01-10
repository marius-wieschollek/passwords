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
use ZipArchive;

/**
 * Class BreachedPasswordsZipAccessException
 *
 * @package OCA\Passwords\Exception\SecurityCheck
 */
class BreachedPasswordsZipAccessException extends Exception {
    const EXCEPTION_CODE = 108;

    public function __construct($errorCode, Throwable $previous = null) {
        if($errorCode === ZipArchive::ER_EXISTS) $errorCode = 'ZipArchive::ER_EXISTS ('.ZipArchive::ER_EXISTS.')';
        if($errorCode === ZipArchive::ER_INCONS) $errorCode = 'ZipArchive::ER_INCONS ('.ZipArchive::ER_INCONS.')';
        if($errorCode === ZipArchive::ER_INVAL) $errorCode = 'ZipArchive::ER_INVAL ('.ZipArchive::ER_INVAL.')';
        if($errorCode === ZipArchive::ER_MEMORY) $errorCode = 'ZipArchive::ER_MEMORY ('.ZipArchive::ER_MEMORY.')';
        if($errorCode === ZipArchive::ER_NOENT) $errorCode = 'ZipArchive::ER_NOENT ('.ZipArchive::ER_NOENT.')';
        if($errorCode === ZipArchive::ER_NOZIP) $errorCode = 'ZipArchive::ER_NOZIP ('.ZipArchive::ER_NOZIP.')';
        if($errorCode === ZipArchive::ER_OPEN) $errorCode = 'ZipArchive::ER_OPEN ('.ZipArchive::ER_OPEN.')';
        if($errorCode === ZipArchive::ER_READ) $errorCode = 'ZipArchive::ER_READ ('.ZipArchive::ER_READ.')';
        if($errorCode === ZipArchive::ER_SEEK) $errorCode = 'ZipArchive::ER_SEEK ('.ZipArchive::ER_SEEK.')';

        parent::__construct('Unable to read breached passwords zip file. Error '.$errorCode, static::EXCEPTION_CODE, $previous);
    }
}
<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

/**
 * Class SmallLocalDbSecurityCheckHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class SmallLocalDbSecurityCheckHelper extends BigLocalDbSecurityCheckHelper {
    const ARCHIVE_URL    = 'https://breached.passwordsapp.org/databases/5-million-json.zip';
    const ARCHIVE_URL_GZ = 'https://breached.passwordsapp.org/databases/5-million-gzip.zip';
    const PASSWORD_DB    = 'smalldb';
}
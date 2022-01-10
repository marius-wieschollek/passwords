<?php
/*
 * @copyright 2022 Passwords App
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
    const ARCHIVE_URL = 'https://breached.passwordsapp.org/databases/5-million-v:version-:format.zip';
    const PASSWORD_DB = 'smalldb';
}
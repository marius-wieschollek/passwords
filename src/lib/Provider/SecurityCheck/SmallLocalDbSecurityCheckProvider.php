<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Provider\SecurityCheck;

/**
 * Class SmallLocalDbSecurityCheckProvider
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class SmallLocalDbSecurityCheckProvider extends BigLocalDbSecurityCheckProvider {
    const ARCHIVE_URL = 'https://breached.passwordsapp.org/databases/5m-v:version-:format.zip';
    const PASSWORD_DB = 'smalldb';
}
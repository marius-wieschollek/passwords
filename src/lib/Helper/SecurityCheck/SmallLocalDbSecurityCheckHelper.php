<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use Exception;
use OCA\Passwords\Exception\SecurityCheck\PasswordDatabaseDownloadException;

/**
 * Class SmallLocalDbSecurityCheckHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class SmallLocalDbSecurityCheckHelper extends BigLocalDbSecurityCheckHelper {

    const LOW_RAM_LIMIT    = 262144;
    const ARCHIVE_URL      = 'https://raw.githubusercontent.com/danielmiessler/SecLists/master/Passwords/Common-Credentials/10-million-password-list-top-1000000.txt';
    const PASSWORD_DB      = 'smalldb';
    const PASSWORD_VERSION = 1;

    /**
     * @param string $txtFile
     *
     * @throws Exception
     */
    protected function downloadPasswordsFile(string $txtFile): void {
        try {
            $client = $this->httpClientService->newClient();
            $client->get(self::ARCHIVE_URL, ['sink' => $txtFile, 'timeout' => 0]);
            unset($client);
        } catch(Exception $e) {
            throw new PasswordDatabaseDownloadException($e);
        }
    }
}
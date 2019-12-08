<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use Exception;
use OCA\Passwords\Helper\Http\FileDownloadHelper;

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
        $request = new FileDownloadHelper();
        $success = $request
            ->setOutputFile($txtFile)
            ->setUrl(self::ARCHIVE_URL)
            ->sendWithRetry();
        if(!$success) {
            throw new Exception('Failed to download common passwords text file: HTTP '.$request->getInfo('http_code'));
        }
        unset($request);
    }
}
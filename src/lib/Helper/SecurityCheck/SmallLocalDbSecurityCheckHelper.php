<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 01.10.17
 * Time: 17:41
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use Exception;
use OCA\Passwords\Helper\Http\FileDownloadHelper;

class SmallLocalDbSecurityCheckHelper extends BigLocalDbSecurityCheckHelper {

    const LOW_RAM_LIMIT = 262144;
    const ARCHIVE_URL   = 'https://raw.githubusercontent.com/danielmiessler/SecLists/master/Passwords/10_million_password_list_top_1000000.txt';
    const PASSWORD_DB   = 'small';

    /**
     * @param string $txtFile
     *
     * @throws Exception
     */
    protected function downloadPasswordsFile(string $txtFile) {
        $request = new FileDownloadHelper();
        $success = $request
            ->setOutputFile($txtFile)
            ->setUrl(self::ARCHIVE_URL)
            ->sendWithRetry();
        if(!$success) throw new Exception('Failed to download common passwords text file');
        unset($request);
    }
}
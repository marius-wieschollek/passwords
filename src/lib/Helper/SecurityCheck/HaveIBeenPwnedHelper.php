<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 16.09.17
 * Time: 22:22
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use OCA\Passwords\Helper\Http\RequestHelper;

/**
 * Class HaveIBeenPwnedHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class HaveIBeenPwnedHelper extends AbstractSecurityCheckHelper {

    const PASSWORD_DB   = 'hibp';
    const SERVICE_URL   = 'https://haveibeenpwned.com/api/v2/pwnedpassword';
    const API_WAIT_TIME = 1750;

    /**
     * @param string $hash
     *
     * @return bool
     */
    public function isHashSecure(string $hash): bool {
        if(!isset($this->hashStatusCache[ $hash ])) {
            if(parent::isHashSecure($hash) && $this->isHashInHibpDb($hash)) {
                $this->addHashToLocalDb($hash);
                $this->hashStatusCache[ $hash ] = false;
            }
        }

        return $this->hashStatusCache[ $hash ];
    }

    /**
     * @inheritdoc
     */
    public function updateDb(): void {
        $this->fileCacheService->clearCache();
    }

    /**
     * @param string $hash
     *
     * @return bool
     */
    protected function isHashInHibpDb(string $hash): bool {
        $request = new RequestHelper();
        $request->setUrl(self::SERVICE_URL)
                ->setPost(['Password' => $hash])
                ->setAcceptResponseCodes([200, 404])
                ->setRetryTimeout(self::API_WAIT_TIME)
                ->setUserAgent(
                    'Nextcloud/'.$this->config->getSystemValue('version').
                    ' Passwords/'.$this->config->getAppValue('installed_version')
                )->sendWithRetry();

        usleep(self::API_WAIT_TIME * 1000);

        return $request->getInfo('http_code') == 200;
    }

    /**
     * @param string $hash
     */
    protected function addHashToLocalDb(string $hash) {
        $file = substr($hash, 0, self::HASH_FILE_KEY_LENGTH).'.json';

        $data = [];
        if($this->fileCacheService->hasFile($file)) {
            $data = $this->fileCacheService->getFile($file)->getContent();
            if($this->config->getAppValue(self::CONFIG_DB_ENCODING) === self::ENCODING_GZIP) $data = gzuncompress($data);
            $data = json_decode($data, true);
        }

        $data[] = $hash;
        $data   = json_encode(array_unique($data));
        if(extension_loaded('zlib')) {
            $data = gzcompress($data);
            $this->config->setAppValue(self::CONFIG_DB_ENCODING, self::ENCODING_GZIP);
        } else {
            $this->config->setAppValue(self::CONFIG_DB_ENCODING, self::ENCODING_PLAIN);
        }
        $this->fileCacheService->putFile($file, $data);
    }
}
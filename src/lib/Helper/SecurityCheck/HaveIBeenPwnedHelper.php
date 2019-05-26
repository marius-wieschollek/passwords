<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use OCA\Passwords\Helper\Http\RequestHelper;

/**
 * Class HaveIBeenPwnedHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class HaveIBeenPwnedHelper extends AbstractSecurityCheckHelper {

    const PASSWORD_DB      = 'hibp';
    const SERVICE_URL      = 'https://api.pwnedpasswords.com/range/';
    const SERVICE_BASE_URL = 'https://api.pwnedpasswords.com/';
    const COOKIE_FILE      = 'nc_pw_hibp_api_cookies.txt';

    protected $checkedRanges = [];

    /**
     * @param string $hash
     *
     * @return bool
     * @throws \Exception
     */
    public function isHashSecure(string $hash): bool {
        if(!isset($this->hashStatusCache[ $hash ])) {
            $isSecure                       = parent::isHashSecure($hash) && !$this->isHashInHibpDb($hash);
            $this->hashStatusCache[ $hash ] = $isSecure;
        }

        return $this->hashStatusCache[ $hash ];
    }

    /**
     * @inheritdoc
     */
    public function updateDb(): void {
        $this->fileCacheService->clearCache();
        $this->config->setAppValue(self::CONFIG_DB_TYPE, static::PASSWORD_DB);
    }

    /**
     * @param string $hash
     *
     * @return bool
     * @throws \Exception
     */
    protected function isHashInHibpDb(string $hash): bool {
        $range = substr($hash, 0, 5);

        if(in_array($range, $this->checkedRanges)) {
            $this->hashStatusCache[ $hash ] = true;

            return false;
        }

        $request  = new RequestHelper();
        $response = $request->setUrl(self::SERVICE_URL.$range)
                            ->setCookieJar($this->config->getTempDir().self::COOKIE_FILE)
                            ->setUserAgent('Passwords App for Nextcloud')
                            ->sendWithRetry();

        if(!$response) throw new \Exception('HIBP API returned invalid response. Status: '.$request->getInfo('http_code'));

        $hashes = $this->processResponse($response, $range);
        $this->addHashToLocalDb($hash, $hashes);
        $this->checkedRanges[] = $range;

        return in_array($hash, $hashes);
    }

    /**
     * @param string $hash
     * @param array  $hashes
     */
    protected function addHashToLocalDb(string $hash, array $hashes): void {
        $data = $this->readPasswordsFile($hash);
        $data = array_merge($data, $hashes);
        $this->writePasswordsFile($hash, $data);
    }

    /**
     * @param $response
     * @param $range
     *
     * @return array
     */
    protected function processResponse($response, $range): array {
        $response = explode("\n", $response);
        $hashes   = [];
        foreach($response as $line) {
            list($subhash, ) = explode(':', $line);

            $currentHash = $range.strtolower($subhash);
            $hashes[]    = $currentHash;

            $this->hashStatusCache[ $currentHash ] = false;
        }

        return $hashes;
    }
}
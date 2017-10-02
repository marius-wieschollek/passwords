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

    const PASSWORD_DB         = 'hibp';
    const SERVICE_URL         = 'https://haveibeenpwned.com/api/v2/pwnedpassword/';
    const SERVICE_BASE_URL    = 'https://haveibeenpwned.com/';
    const MAGIC_NUMBER_OFFSET = 18;
    const API_WAIT_TIME       = 2;
    const COOKIE_FILE         = 'nc_pw_hibp_api_cookies.txt';

    /**
     * @var int
     */
    protected static $lastRequestTime = 0;

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
     * @throws \Exception
     */
    protected function isHashInHibpDb(string $hash): bool {
        if(self::$lastRequestTime != 0 && time() - self::$lastRequestTime < self::API_WAIT_TIME) {
            sleep(self::API_WAIT_TIME);
        }

        $apiUrl = self::SERVICE_URL.$hash;
        $request = new RequestHelper();
        $request->setUrl($apiUrl)
                ->setAcceptResponseCodes([200, 404, 503])
                ->setRetryTimeout(self::API_WAIT_TIME)
                ->setCookieJar($this->config->getTempDir().self::COOKIE_FILE)
                ->setUserAgent(
                    'Nextcloud/'.$this->config->getSystemValue('version').
                    ' Passwords/'.$this->config->getAppValue('installed_version').
                    ' Instance/'.$this->config->getSystemValue('ocb335283f10')
                )->sendWithRetry();

        if($request->getInfo('http_code') === 503) {
            sleep(4);
            $request->setUrl($this->getCloudFlareRedirectUrl($request->getResponseBody()))
                    ->setAcceptResponseCodes([200, 404])
                    ->setHeaderData(['Referer' => $apiUrl])
                    ->sendWithRetry();
        }

        $responseCode = $request->getInfo('http_code');
        if(!in_array($responseCode, [200, 404])) {
            throw new \Exception('HIBP API returned invalid response code: '.$responseCode);
        }

        self::$lastRequestTime = time();

        return $responseCode == 200;
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

    /**
     * @param string $html
     *
     * @return string
     */
    protected function getCloudFlareRedirectUrl(string $html) {
        $getFields                 = $this->getHiddenFields($html);
        $getFields['jschl_answer'] = $this->getMagicNumber($html, self::MAGIC_NUMBER_OFFSET);

        return $this->getTargetUrl($html, self::SERVICE_BASE_URL, $getFields);
    }

    /**
     * @param string $html
     * @param int    $add
     *
     * @return int
     */
    protected function getMagicNumber(string $html, int $add = 0) {
        preg_match("/e,a,k,i,n,g,f, \w+={\"(\w+)\":([+()!\[\]]+)/", $html, $matches);
        $key  = $matches[1];
        $calc = $matches[2];

        preg_match_all("/{$key}([+\-\*\/])=([+()!\[\]]+);/", $html, $matches);
        foreach ($matches[1] as $i => $v) {
            $calc = '('.$calc.')'.$v.'('.$matches[2][ $i ].')';
        }

        $calc = str_replace('!+[]', 1, $calc);
        $calc = str_replace('!![]', 1, $calc);
        $calc = str_replace('[]', 0, $calc);

        $number = 0;
        preg_match_all("/(\([1+0]+\))/", $calc, $matches);
        foreach ($matches[0] as $match) {
            eval('$number='.$match.';');
            $calc = str_replace($match, $number, $calc);
        }
        $calc = preg_replace("/([0-9]+)\+/", "$1", $calc);
        eval('$number='.$calc.';');

        return $number + $add;
    }

    /**
     * @param string $html
     *
     * @return array
     */
    protected function getHiddenFields(string $html) {
        preg_match_all("/input\s+type=\"hidden\"\s+name=\"(\S+)\"\s+value=\"(\S+)\"/", $html, $matches);

        $fields = [];
        foreach ($matches[1] as $i => $key) {
            $fields[ $key ] = $matches[2][ $i ];
        }

        return $fields;
    }

    /**
     * @param $html
     * @param $baseUrl
     * @param $getFields
     *
     * @return string
     */
    protected function getTargetUrl($html, $baseUrl, $getFields) {
        preg_match_all("/action=\"(\S+)\"/", $html, $matches);

        return $baseUrl.$matches[1][0].'?'.http_build_query($getFields);
    }
}
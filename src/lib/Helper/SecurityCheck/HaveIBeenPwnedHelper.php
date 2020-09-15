<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use Exception;
use GuzzleHttp\Exception\ClientException;
use OCA\Passwords\Exception\SecurityCheck\InvalidHibpApiResponseException;

/**
 * Class HaveIBeenPwnedHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class HaveIBeenPwnedHelper extends AbstractSecurityCheckHelper {

    const PASSWORD_DB      = 'hibp';
    const SERVICE_URL      = 'https://api.pwnedpasswords.com/range/';
    const COOKIE_FILE      = 'nc_pw_hibp_api_cookies.txt';

    protected array $checkedRanges = [];

    /**
     * @param string $hash
     *
     * @return bool
     * @throws Exception
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
     * @throws Exception
     */
    protected function isHashInHibpDb(string $hash): bool {
        $range = substr($hash, 0, 5);

        if(in_array($range, $this->checkedRanges)) {
            $this->hashStatusCache[ $hash ] = true;

            return false;
        }

        $responseData = $this->executeApiRequest($range);
        $hashes       = $this->processResponse($responseData, $range);
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
            [$subhash,] = explode(':', $line);

            $currentHash = $range.strtolower($subhash);
            $hashes[]    = $currentHash;

            $this->hashStatusCache[ $currentHash ] = false;
        }

        return $hashes;
    }

    /**
     * Fetch data from the HIBP api
     *
     * @param string $range
     *
     * @return resource|string
     * @throws Exception
     */
    protected function executeApiRequest(string $range) {
        try {
            $client   = $this->httpClientService->newClient();
            $response = $client->get(self::SERVICE_URL.$range, ['headers' => ['User-Agent' => 'Passwords App for Nextcloud']]);
        } catch(ClientException $e) {
            if($e->getResponse()->getStatusCode() === 404) {
                return '';
            }

            throw new InvalidHibpApiResponseException(null, $e);
        } catch(Exception $e) {
            throw new InvalidHibpApiResponseException(null, $e);
        }

        $responseData = $response->getBody();
        if(!$responseData) throw new InvalidHibpApiResponseException($response);

        return $responseData;
    }
}
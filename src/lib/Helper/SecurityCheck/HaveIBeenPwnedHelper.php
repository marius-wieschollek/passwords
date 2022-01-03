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

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use OCA\Passwords\Exception\SecurityCheck\InvalidHibpApiResponseException;

/**
 * Class HaveIBeenPwnedHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class HaveIBeenPwnedHelper extends AbstractSecurityCheckHelper {

    const PASSWORD_DB        = 'hibp';
    const CONFIG_SERVICE_URL = 'passwords/hibp/url';
    const SERVICE_URL        = 'https://api.pwnedpasswords.com/range/';

    /**
     * @var array
     */
    protected array $checkedRanges = [];

    /**
     * @var bool
     */
    protected bool $isAvailable = false;

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
     * @inheritdoc
     */
    public function isAvailable(): bool {
        if($this->isAvailable) return $this->isAvailable;

        try {
            $client   = $this->httpClientService->newClient();
            $response = $client->head($this->getApiUrl('fffff'), [RequestOptions::TIMEOUT => 5]);

            $this->isAvailable = $response->getStatusCode() === 200;

            return $this->isAvailable;
        } catch(Exception $e) {
            return false;
        }
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
            if(!array_key_exists($hash, $this->hashStatusCache)) {
                $hashes = array_keys($this->hashStatusCache);

                return $this->checkForHashInHashes($hashes, $hash);
            } else {
                return !$this->hashStatusCache[ $hash ];
            }
        }

        $responseData = $this->executeApiRequest($range);
        $hashes       = $this->processResponse($responseData, $range);
        $this->addHashToLocalDb($hash, $hashes);
        $this->checkedRanges[] = $range;

        return $this->checkForHashInHashes($hashes, $hash);
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
            $response = $client->get($this->getApiUrl($range), ['headers' => ['User-Agent' => 'Passwords App for Nextcloud']]);
        } catch(ClientException $e) {
            if($e->getResponse()->getStatusCode() === 404 || $e->getResponse()->getStatusCode() === 502) {
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

    /**
     * @param string $range
     *
     * @return string
     */
    protected function getApiUrl(string $range): string {
        return str_replace(':range', $range, $this->config->getAppValue(static::CONFIG_SERVICE_URL, static::SERVICE_URL));
    }
}
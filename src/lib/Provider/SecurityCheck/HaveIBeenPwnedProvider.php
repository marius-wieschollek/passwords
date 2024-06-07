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

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use OCA\Passwords\Exception\SecurityCheck\InvalidHibpApiResponseException;

/**
 * Class HaveIBeenPwnedProvider
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class HaveIBeenPwnedProvider extends AbstractSecurityCheckProvider {

    const PASSWORD_DB        = 'hibp';
    const CONFIG_SERVICE_URL = 'passwords/hibp/url';
    const SERVICE_URL        = 'https://api.pwnedpasswords.com/range/:range';

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
     * @throws Exception
     */
    public function getHashRange(string $range): array {
        $hibpRange = $this->makeHibpRange($range);

        if(!isset($this->checkedRanges[ $hibpRange ])) {
            $hashes = $this->executeApiRequest($hibpRange);
        } else {
            $hashes = array_keys($this->hashStatusCache);
        }

        $matchingHashes = [];
        foreach($hashes as $hash) {
            if(str_starts_with($hash, $range)) {
                $matchingHashes[] = $hash;
            }
        }

        return $matchingHashes;
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
        $range = $this->makeHibpRange($hash);

        if(isset($this->checkedRanges[ $range ])) {
            if(strlen($hash) !== 40 && !array_key_exists($hash, $this->hashStatusCache)) {
                $hashes = array_keys($this->hashStatusCache);

                return $this->checkForHashInHashes($hashes, $hash);
            }

            return array_key_exists($hash, $this->hashStatusCache) && !$this->hashStatusCache[ $hash ];
        }

        $hashes = $this->executeApiRequest($range);
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
     * @return array
     * @throws Exception
     */
    protected function executeApiRequest(string $range): array {
        try {
            $client   = $this->httpClientService->newClient();
            $response = $client->get($this->getApiUrl($range), ['headers' => ['User-Agent' => 'Passwords App for Nextcloud']]);
        } catch(ClientException $e) {
            if($e->getResponse()->getStatusCode() === 404 || $e->getResponse()->getStatusCode() === 502) {
                $this->checkedRanges[ $range ] = true;

                return [];
            }

            throw new InvalidHibpApiResponseException(null, $e);
        } catch(Exception $e) {
            throw new InvalidHibpApiResponseException(null, $e);
        }

        $responseData = $response->getBody();
        if(!$responseData) throw new InvalidHibpApiResponseException($response);

        $hashes = $this->processResponse($responseData, $range);
        $this->addHashToLocalDb($range, $hashes);
        $this->checkedRanges[ $range ] = true;

        return $hashes;
    }

    /**
     * @param string $range
     *
     * @return string
     */
    protected function getApiUrl(string $range): string {
        return str_replace(':range', $range, $this->config->getAppValue(static::CONFIG_SERVICE_URL, static::SERVICE_URL));
    }

    /**
     * @param string $hash
     *
     * @return string
     */
    protected function makeHibpRange(string $hash): string {
        $range = substr($hash, 0, 5);

        return $range;
    }
}
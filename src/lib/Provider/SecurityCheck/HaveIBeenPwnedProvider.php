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
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\LoggingService;
use OCP\Cache\CappedMemoryCache;
use OCP\Http\Client\IClientService;
use Throwable;

/**
 * Class HaveIBeenPwnedProvider
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class HaveIBeenPwnedProvider extends AbstractSecurityCheckProvider {

    const string PASSWORD_DB        = 'hibp';
    const string CONFIG_SERVICE_URL = 'passwords/hibp/url';
    const string SERVICE_URL        = 'https://api.pwnedpasswords.com/range/:range';
    const int    RANGE_CACHE_SIZE   = 64;

    /**
     * @var CappedMemoryCache
     */
    protected CappedMemoryCache $checkedRanges;

    /**
     * @var bool
     */
    protected bool $isAvailable = false;

    /**
     * @param LoggingService       $logger
     * @param IClientService       $httpClientService
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $configurationService
     */
    public function __construct(
        LoggingService           $logger,
        protected IClientService $httpClientService,
        FileCacheService         $fileCacheService,
        ConfigurationService     $configurationService
    ) {
        parent::__construct($logger, $fileCacheService, $configurationService);
        $this->checkedRanges = new CappedMemoryCache(self::RANGE_CACHE_SIZE);
    }

    /**
     * @param string $hash
     *
     * @return bool
     * @throws Exception
     */
    public function isHashSecure(string $hash): bool {
        if (!$this->hashStatusCache->hasKey($hash)) {
            $isSecure = parent::isHashSecure($hash) && !$this->isHashInHibpDb($hash);
            $this->hashStatusCache->set($hash, $isSecure);
        }

        return (bool)$this->hashStatusCache->get($hash);
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function getHashRange(string $range): array {
        $hibpRange = $this->makeHibpRange($range);

        if (!$this->checkedRanges->hasKey($hibpRange)) {
            $hashes = $this->executeApiRequest($hibpRange);
        } else {
            $hashes = $this->checkedRanges->get($hibpRange);
        }

        $matchingHashes = [];
        foreach ($hashes as $hash) {
            if (str_starts_with($hash, $range)) {
                $matchingHashes[] = $hash;
            }
        }

        return $matchingHashes;
    }

    /**
     * @inheritdoc
     */
    public function updateDb(): void {
        $this->fileCache->clearCache();
        $this->config->setAppValue(self::CONFIG_DB_TYPE, static::PASSWORD_DB);
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(): bool {
        if ($this->isAvailable) {
            return $this->isAvailable;
        }

        try {
            $client = $this->httpClientService->newClient();
            $response = $client->head(
                $this->getApiUrl('fffff'),
                [
                    RequestOptions::HEADERS => ['User-Agent' => self::PASSWORDS_USER_AGENT],
                    RequestOptions::TIMEOUT => 5
                ]
            );

            $this->isAvailable = $response->getStatusCode() === 200;

            return $this->isAvailable;
        } catch (Throwable $e) {
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

        if ($this->checkedRanges->hasKey($range)) {
            return $this->checkForHashInHashes($this->checkedRanges->get($range), $hash);
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
        $hashes = [];
        foreach ($response as $line) {
            [$subhash,] = explode(':', $line);

            $hashes[] = $range . strtolower($subhash);
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
            $client = $this->httpClientService->newClient();
            $response = $client->get(
                $this->getApiUrl($range),
                [RequestOptions::HEADERS => ['User-Agent' => self::PASSWORDS_USER_AGENT]]
            );
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404 || $e->getResponse()->getStatusCode() === 502) {
                $this->checkedRanges->set($range, []);

                return [];
            }

            throw new InvalidHibpApiResponseException(null, $e);
        } catch (Exception $e) {
            throw new InvalidHibpApiResponseException(null, $e);
        }

        $responseData = $response->getBody();
        if (!$responseData) {
            throw new InvalidHibpApiResponseException($response);
        }

        $hashes = $this->processResponse($responseData, $range);
        $this->addHashToLocalDb($range, $hashes);
        $this->checkedRanges->set($range, $hashes);

        return $hashes;
    }

    /**
     * @param string $range
     *
     * @return string
     */
    protected function getApiUrl(string $range): string {
        return str_replace(
            ':range',
            $range,
            $this->config->getAppValue(static::CONFIG_SERVICE_URL, static::SERVICE_URL)
        );
    }

    /**
     * @param string $hash
     *
     * @return string
     */
    protected function makeHibpRange(string $hash): string {
        return substr($hash, 0, 5);
    }
}
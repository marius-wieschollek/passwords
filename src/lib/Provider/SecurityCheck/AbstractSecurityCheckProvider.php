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

use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\LoggingService;
use OCP\Cache\CappedMemoryCache;
use Throwable;

/**
 * Class AbstractSecurityCheckProvider
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
abstract class AbstractSecurityCheckProvider implements SecurityCheckProviderInterface {

    const        PASSWORD_DB          = 'none';
    const int    HASH_FILE_KEY_LENGTH = 3;
    const string CONFIG_DB_TYPE       = 'passwords/localdb/type';
    const int    HASH_CACHE_SIZE      = 4096;
    const string PASSWORDS_USER_AGENT = 'Passwords App for Nextcloud';
    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCache;
    /**
     * @var CappedMemoryCache
     */
    protected CappedMemoryCache $hashStatusCache;

    /**
     * AbstractSecurityCheckProvider constructor.
     *
     * @param LoggingService       $logger
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $config
     */
    public function __construct(
        protected LoggingService       $logger,
        FileCacheService               $fileCacheService,
        protected ConfigurationService $config
    ) {
        $this->fileCache = $fileCacheService->getCacheService($fileCacheService::PASSWORDS_CACHE);
        $this->hashStatusCache = new CappedMemoryCache(self::HASH_CACHE_SIZE);
    }

    /**
     * Checks if the given password is known to be insecure
     *
     * @param string $password
     *
     * @return bool
     */
    public function isPasswordSecure(string $password): bool {
        return $this->isHashSecure(sha1($password));
    }

    /**
     * Checks if the given hash belongs to an insecure password
     *
     * @param string $hash
     *
     * @return bool
     */
    public function isHashSecure(string $hash): bool {
        if (empty($hash)) {
            return false;
        }

        if (!$this->hashStatusCache->hasKey($hash)) {
            $hashes = $this->readPasswordsFile($hash);
            $this->hashStatusCache->set($hash, !$this->checkForHashInHashes($hashes, $hash));
        }

        return (bool)$this->hashStatusCache->get($hash);
    }

    /**
     * Checks if the local password database needs to be updated
     *
     * @return bool
     */
    function dbUpdateRequired(): bool {
        $installedType = $this->config->getAppValue(self::CONFIG_DB_TYPE);

        return $installedType !== static::PASSWORD_DB;
    }

    /**
     * @param string $hash
     *
     * @return array
     */
    protected function readPasswordsFile(string $hash): array {
        $file = $this->getPasswordsFileName($hash);
        if (!$this->fileCache->hasFile($file)) {
            return [];
        }

        try {
            $data = $this->fileCache->getFile($file)->getContent();
            if (extension_loaded('zlib')) {
                $data = gzuncompress($data);
            }
        } catch (Throwable $e) {
            $this->logger->logException($e);

            return [];
        }

        $data = json_decode($data, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param string $hash
     * @param array  $hashes
     */
    protected function writePasswordsFile(string $hash, array $hashes): void {
        $file = $this->getPasswordsFileName($hash);

        $data = json_encode(array_unique($hashes));
        if (extension_loaded('zlib')) {
            $data = gzcompress($data);
        }

        $this->fileCache->putFile($file, $data);
    }

    /**
     * @param string $hash
     *
     * @return string
     */
    protected function getPasswordsFileName(string $hash): string {
        $file = substr($hash, 0, self::HASH_FILE_KEY_LENGTH) . '.json';

        return extension_loaded('zlib') ? $file . '.gz' : $file;
    }

    /**
     * @param $hashes
     * @param $hash
     *
     * @return bool
     */
    protected function checkForHashInHashes($hashes, $hash): bool {
        $length = strlen($hash);
        if ($length === 40) {
            return in_array($hash, $hashes);
        } else {
            foreach ($hashes as $current) {
                if (substr($current, 0, $length) === $hash) {
                    return true;
                }
            }

            return false;
        }
    }
}
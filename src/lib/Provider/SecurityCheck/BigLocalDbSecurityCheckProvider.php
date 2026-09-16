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
use GuzzleHttp\RequestOptions;
use OCA\Passwords\Exception\SecurityCheck\BreachedPasswordsZipAccessException;
use OCA\Passwords\Exception\SecurityCheck\BreachedPasswordsZipExtractException;
use OCA\Passwords\Exception\SecurityCheck\PasswordDatabaseDownloadException;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\LoggingService;
use OCP\Http\Client\IClientService;
use Throwable;
use ZipArchive;

/**
 * Class BigLocalDbSecurityCheckProvider
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class BigLocalDbSecurityCheckProvider extends AbstractSecurityCheckProvider {

    const        ARCHIVE_URL       = 'https://breached.passwordsapp.org/databases/25m-v:version-:format.zip';
    const string CONFIG_DB_VERSION = 'passwords/localdb/version';
    const string CONFIG_DB_SOURCE  = 'passwords/localdb/source';
    const        PASSWORD_DB       = 'bigdb';
    const int    PASSWORD_VERSION  = 16;

    /**
     * @param LoggingService       $logger
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $config
     * @param IClientService       $httpClientService
     */
    public function __construct(
        LoggingService           $logger,
        FileCacheService         $fileCacheService,
        ConfigurationService     $config,
        protected IClientService $httpClientService
    ) {
        parent::__construct($logger, $fileCacheService, $config);
    }

    public function getHashRange(string $range): array {
        $hashes = $this->readPasswordsFile($range);

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
    public function dbUpdateRequired(): bool {
        return !$this->isLocalDbValid() || parent::dbUpdateRequired();
    }

    /**
     * @return bool
     */
    public function isLocalDbValid(): bool {
        try {
            $installedVersion = intval($this->config->getAppValue(self::CONFIG_DB_VERSION));
            if ($installedVersion !== static::PASSWORD_VERSION) {
                return false;
            }

            $info = $this->fileCache->getCacheInfo();
            if ($info['files'] < 64) {
                return false;
            }
        } catch (Exception $e) {
            $this->logger->logException($e);
        }

        return true;
    }

    /**
     * @inheritdoc
     * @throws Throwable
     */
    public function updateDb(): void {
        ini_set('max_execution_time', 0);
        if (!$this->isAvailable() || (intval(ini_get('max_execution_time')) !== 0 && intval(
                    ini_get('max_execution_time')
                ) < 7200)) {
            throw new \Exception('Password security check service not available. Consult manual.');
        }

        $zipFile = $this->downloadPasswordsFile();
        $this->unpackPasswordsFile($zipFile);

        $this->config->setAppValue(self::CONFIG_DB_TYPE, static::PASSWORD_DB);
        $this->config->setAppValue(self::CONFIG_DB_VERSION, static::PASSWORD_VERSION);
        $this->logPasswordUpdate();
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(): bool {
        return extension_loaded('zip');
    }

    /**
     * @throws Throwable
     */
    protected function downloadPasswordsFile(): string {
        $zipFile = $this->config->getTempDir() . uniqid() . '.zip';

        try {
            $client = $this->httpClientService->newClient();
            $client->get(
                $this->getArchiveUrl(),
                [
                    RequestOptions::HEADERS => ['User-Agent' => self::PASSWORDS_USER_AGENT],
                    RequestOptions::SINK    => $zipFile,
                    RequestOptions::TIMEOUT => 0
                ]
            );

            return $zipFile;
        } catch (Exception $e) {
            throw new PasswordDatabaseDownloadException($e);
        }
    }

    /**
     * @param string $zipFile
     *
     * @throws BreachedPasswordsZipAccessException
     * @throws BreachedPasswordsZipExtractException
     * @throws Throwable
     */
    protected function unpackPasswordsFile(string $zipFile): void {
        try {
            $zip = new ZipArchive;
            $result = $zip->open($zipFile);
            if ($result === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $contents = $zip->getFromIndex($i);
                    if (!$contents) {
                        throw new BreachedPasswordsZipExtractException($zip->getStatusString());
                    }

                    $name = $zip->getNameIndex($i);
                    if (!$name) {
                        throw new BreachedPasswordsZipExtractException($name);
                    }

                    $this->fileCache->putFile($name, $contents);
                }
            } else {
                throw new BreachedPasswordsZipAccessException($result);
            }
        } catch (Throwable $e) {
            if (is_file($zipFile)) {
                @unlink($zipFile);
            }
            throw $e;
        }
        unlink($zipFile);
    }

    /**
     *
     */
    protected function logPasswordUpdate(): void {
        $ram = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $this->logger->info(["Updated local password db. DB: %s, RAM: %s MiB", static::PASSWORD_DB, $ram]);
    }

    /**
     * @return string
     */
    protected function getArchiveUrl(): string {
        $format = extension_loaded('zlib') ? 'gzip' : 'json';

        return str_replace([':format', ':version'],
                           [$format, static::PASSWORD_VERSION],
                           $this->config->getAppValue(static::CONFIG_DB_SOURCE, static::ARCHIVE_URL));
    }
}
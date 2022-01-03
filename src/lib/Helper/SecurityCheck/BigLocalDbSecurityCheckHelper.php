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
use OCA\Passwords\Exception\SecurityCheck\BreachedPasswordsZipAccessException;
use OCA\Passwords\Exception\SecurityCheck\BreachedPasswordsZipExtractException;
use OCA\Passwords\Exception\SecurityCheck\PasswordDatabaseDownloadException;
use Throwable;
use ZipArchive;

/**
 * Class BigLocalDbSecurityCheckHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class BigLocalDbSecurityCheckHelper extends AbstractSecurityCheckHelper {

    const ARCHIVE_URL       = 'https://breached.passwordsapp.org/databases/25-million-:format.zip';
    const CONFIG_DB_VERSION = 'passwords/localdb/version';
    const CONFIG_DB_SOURCE  = 'passwords/localdb/source';
    const PASSWORD_DB       = 'bigdb';
    const PASSWORD_VERSION  = 8;

    /**
     * @inheritdoc
     */
    public function dbUpdateRequired(): bool {
        try {
            $installedVersion = intval($this->config->getAppValue(self::CONFIG_DB_VERSION));
            if($installedVersion !== static::PASSWORD_VERSION) return true;

            $info = $this->fileCacheService->getCacheInfo();
            if($info['files'] < 4096) return true;
        } catch(Exception $e) {
            $this->logger->logException($e);
        }

        return parent::dbUpdateRequired();
    }

    /**
     * @inheritdoc
     * @throws Throwable
     */
    public function updateDb(): void {
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
        $zipFile = $this->config->getTempDir().uniqid().'.zip';

        try {
            $client = $this->httpClientService->newClient();
            $client->get($this->getArchiveUrl(), ['sink' => $zipFile, 'timeout' => 0]);

            return $zipFile;
        } catch(Exception $e) {
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
            $zip    = new ZipArchive;
            $result = $zip->open($zipFile);
            if($result === true) {
                for($i = 0; $i < $zip->numFiles; $i++) {
                    $contents = $zip->getFromIndex($i);
                    if(!$contents) {
                        throw new BreachedPasswordsZipExtractException($zip->getStatusString());
                    }

                    $name = $zip->getNameIndex($i);
                    if(!$name) {
                        throw new BreachedPasswordsZipExtractException($name);
                    }

                    $this->fileCacheService->putFile($name, $contents);
                }
            } else {
                throw new BreachedPasswordsZipAccessException($result);
            }
        } catch(Throwable $e) {
            if(is_file($zipFile)) @unlink($zipFile);
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
        $format = extension_loaded('zlib') ? 'gzip':'json';

        return str_replace(':format', $format, $this->config->getAppValue(static::CONFIG_DB_SOURCE, static::ARCHIVE_URL));
    }
}
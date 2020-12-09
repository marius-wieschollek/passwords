<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use Exception;
use OCA\Passwords\Exception\SecurityCheck\BreachedPasswordsZipAccessException;
use OCA\Passwords\Exception\SecurityCheck\PasswordDatabaseDownloadException;
use OCA\Passwords\Exception\SecurityCheck\BreachedPasswordsFileAccessException;
use Throwable;
use ZipArchive;

/**
 * Class BigLocalDbSecurityCheckHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class BigLocalDbSecurityCheckHelper extends AbstractSecurityCheckHelper {

    const LOW_RAM_LIMIT     = 4194304;
    const ARCHIVE_URL       = 'https://archive.org/download/10MillionPasswords/10-million-combos.zip';
    const CONFIG_DB_VERSION = 'passwords/localdb/version';
    const PASSWORD_DB       = 'bigdb';
    const PASSWORD_VERSION  = 1;

    /**
     * @inheritdoc
     */
    public function dbUpdateRequired(): bool {
        try {
            $installedVersion = $this->config->getAppValue(self::CONFIG_DB_VERSION);
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
        ini_set('memory_limit', -1);
        $txtFile = $this->config->getTempDir().uniqid().'.txt';

        $this->downloadPasswordsFile($txtFile);
        if($this->isLowMemorySystem()) {
            $this->lowMemoryHashAlgorithm($txtFile);
        } else {
            $this->highMemoryHashAlgorithm($txtFile);
        }

        gc_collect_cycles();
        gc_mem_caches();

        $this->config->setAppValue(self::CONFIG_DB_TYPE, static::PASSWORD_DB);
        $this->config->setAppValue(self::CONFIG_DB_VERSION, static::PASSWORD_VERSION);
        $this->logPasswordUpdate();
    }

    /**
     * @param string $txtFile
     *
     * @throws Throwable
     */
    protected function downloadPasswordsFile(string $txtFile): void {
        $zipFile = $this->config->getTempDir().uniqid().'.zip';

        try {
            $client = $this->httpClientService->newClient();
            $client->get(self::ARCHIVE_URL, ['sink' => $zipFile, 'timeout' => 0]);
            unset($client);
        } catch(Exception $e) {
            throw new PasswordDatabaseDownloadException($e);
        }

        $this->unpackPasswordsFile($zipFile, $txtFile);
    }

    /**
     * @param $zipFile
     * @param $txtFile
     *
     * @throws Throwable
     */
    protected function unpackPasswordsFile(string $zipFile, string $txtFile): void {
        try {
            $zip = new ZipArchive;
            if($zip->open($zipFile) === true) {
                $name = $zip->getNameIndex(0);
                $zip->extractTo($this->config->getTempDir(), $name);
                rename($this->config->getTempDir().$name, $txtFile);
                $zip->close();
            } else {
                throw new BreachedPasswordsZipAccessException();
            }
        } catch(Throwable $e) {
            if(is_file($txtFile)) @unlink($txtFile);
            if(is_file($zipFile)) @unlink($zipFile);
            throw $e;
        }
        unlink($zipFile);
    }

    /**
     * This way to create the hashes takes only 120MB of ram.
     * But it also needs 15x the time.
     *
     * @param string $txtFile
     *
     * @throws BreachedPasswordsFileAccessException
     */
    protected function lowMemoryHashAlgorithm(string $txtFile): void {
        $null = null;
        for($i = 0; $i < 16; $i++) {
            $hexKey = dechex($i);
            $hashes = [];
            $file   = fopen($txtFile, 'r');
            if($file === false) throw new BreachedPasswordsFileAccessException($file);

            while(($line = fgets($file)) !== false) {
                [$first, $second] = explode("\t", "$line\t000000");

                $hash = sha1($first);
                if($hash[0] === $hexKey) {
                    $key = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
                    if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
                    $hashes[ $key ][ $hash ] = &$null;
                }

                $hash = sha1($second);
                if($hash[0] === $hexKey) {
                    $key = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
                    if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
                    $hashes[ $key ][ $hash ] = &$null;
                }
            }
            fclose($file);
            $this->storeHashes($hashes);
            unset($hashes);
            gc_collect_cycles();
            gc_mem_caches();
        }
        unlink($txtFile);
    }

    /**
     * This way to create the hashes takes up to 1800MB of ram.
     * It is also quite fast.
     *
     * @param string $txtFile
     *
     * @throws BreachedPasswordsFileAccessException
     */
    protected function highMemoryHashAlgorithm(string $txtFile): void {
        $null   = null;
        $hashes = [];
        $file   = fopen($txtFile, 'r');
        if($file === false) throw new BreachedPasswordsFileAccessException($file);

        while(($line = fgets($file)) !== false) {
            [$first, $second] = explode("\t", "$line\t000000");

            $hash = sha1(trim($first));
            $key  = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
            if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
            $hashes[ $key ][ $hash ] = &$null;

            $hash = sha1($second);
            $key  = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
            if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
            $hashes[ $key ][ $hash ] = &$null;
        }
        fclose($file);
        $this->storeHashes($hashes);

        unlink($txtFile);
    }

    /**
     * @param array $hashes
     */
    protected function storeHashes(array $hashes): void {
        foreach($hashes as $key => $data) {
            $this->writePasswordsFile($key, array_keys($data));
        }
    }

    /**
     * @return bool
     */
    protected function isLowMemorySystem(): bool {
        if(preg_match('/MemAvailable:\s+([0-9]+)/', @file_get_contents('/proc/meminfo'), $matches)) {
            return $matches[1] < static::LOW_RAM_LIMIT;
        }

        return true;
    }

    /**
     *
     */
    protected function logPasswordUpdate(): void {
        $ram = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $this->logger->info(["Updated local password db. DB: %s, RAM: %s MiB", static::PASSWORD_DB, $ram]);
    }
}
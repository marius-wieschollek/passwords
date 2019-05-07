<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use Exception;
use OCA\Passwords\Helper\Http\FileDownloadHelper;
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

        $this->config->setAppValue(self::CONFIG_DB_TYPE, static::PASSWORD_DB);
        $this->config->setAppValue(self::CONFIG_DB_VERSION, static::PASSWORD_VERSION);
        $this->logPasswordUpdate();
    }

    /**
     * @param string $txtFile
     *
     * @throws Exception
     * @throws Throwable
     */
    protected function downloadPasswordsFile(string $txtFile): void {
        $zipFile = $this->config->getTempDir().uniqid().'.zip';

        $request = new FileDownloadHelper();
        $success = $request
            ->setOutputFile($zipFile)
            ->setUrl(self::ARCHIVE_URL)
            ->sendWithRetry();
        if(!$success) throw new Exception('Failed to download common passwords zip file: HTTP'.$request->getInfo('http_code'));
        unset($request);

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
                throw new Exception('Unable to read common passwords zip file');
            }
        } catch(Throwable $e) {
            if(is_file($txtFile)) @unlink($txtFile);
            if(is_file($zipFile)) @unlink($zipFile);
            throw $e;
        }
        unlink($zipFile);
    }

    /**
     * This way to create the hashes takes only 116MB of ram.
     * But it also needs 15x the time.
     *
     * @param string $txtFile
     */
    protected function lowMemoryHashAlgorithm(string $txtFile): void {
        $null = null;
        for($i = 0; $i < 16; $i++) {
            $hexKey = dechex($i);
            $hashes = [];
            $file   = fopen($txtFile, 'r');
            while(($line = fgets($file)) !== false) {
                list($first, $second) = explode("\t", "$line\t000000");

                $hash = sha1($first);
                if($hash[0] == $hexKey) {
                    $key = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
                    if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
                    $hashes[ $key ][ $hash ] = &$null;
                }

                $hash = sha1($second);
                if($hash[0] == $hexKey) {
                    $key = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
                    if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
                    $hashes[ $key ][ $hash ] = &$null;
                }
            }
            fclose($file);
            $this->storeHashes($hashes);
        }
        unlink($txtFile);
    }

    /**
     * This way to create the hashes takes up to 1.650GB of ram.
     * It is also quite fast.
     *
     * @param string $txtFile
     */
    protected function highMemoryHashAlgorithm(string $txtFile): void {
        $null   = null;
        $hashes = [];
        $file   = fopen($txtFile, 'r');
        while(($line = fgets($file)) !== false) {
            list($first, $second) = explode("\t", "$line\t000000");

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
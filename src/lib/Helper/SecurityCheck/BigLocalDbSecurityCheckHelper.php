<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 16.09.17
 * Time: 22:39
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use Exception;
use OCA\Passwords\Helper\Http\FileDownloadHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCP\ILogger;
use Throwable;
use ZipArchive;

/**
 * Class BigLocalDbSecurityCheckHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class BigLocalDbSecurityCheckHelper extends AbstractSecurityCheckHelper {

    const LOW_RAM_LIMIT = 4194304;
    const ARCHIVE_URL   = 'https://archive.org/download/10MillionPasswords/10-million-combos.zip';
    const PASSWORD_DB   = 'large';

    /**
     * @var ILogger
     */
    protected $log;

    /**
     * BigPasswordDbHelper constructor.
     *
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $configurationService
     * @param ILogger              $log
     */
    public function __construct(FileCacheService $fileCacheService, ConfigurationService $configurationService, ILogger $log) {
        parent::__construct($fileCacheService, $configurationService);
        $this->log = $log;
    }

    /**
     * @inheritdoc
     */
    public function dbUpdateRequired(): bool {
        $installedType = $this->config->getAppValue(self::CONFIG_DB_TYPE);

        return $this->fileCacheService->isCacheEmpty() || $installedType != static::PASSWORD_DB;
    }

    /**
     * @inheritdoc
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
        if(!$success) throw new Exception('Failed to download common passwords zip file');
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
        } catch (Throwable $e) {
            if(is_file($txtFile)) @unlink($txtFile);
            if(is_file($zipFile)) @unlink($zipFile);
            throw $e;
        }
        unlink($zipFile);
    }

    /**
     * This way to create the hashes takes only 115MB of ram.
     * But it also needs 15x the time.
     *
     * @param string $txtFile
     */
    protected function lowMemoryHashAlgorithm(string $txtFile): void {
        $null = null;
        for ($i = 0; $i < 16; $i++) {
            $hexKey = dechex($i);
            $hashes = [];
            $fh     = fopen($txtFile, 'r');
            while (($line = fgets($fh)) !== false) {
                list($a, $b) = explode("\t", "$line\t000000");

                $hash = sha1($a);
                if($hash[0] == $hexKey) {
                    $key = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
                    if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
                    $hashes[ $key ][ $hash ] = &$null;
                }

                $hash = sha1($b);
                if($hash[0] == $hexKey) {
                    $key = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
                    if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
                    $hashes[ $key ][ $hash ] = &$null;
                }
            }
            fclose($fh);
            $this->storeHashes($hashes);
        }
        unlink($txtFile);
    }

    /**
     * This way to create the hashes takes up to 1.625GB of ram.
     * It is also quite fast.
     *
     * @param string $txtFile
     */
    protected function highMemoryHashAlgorithm(string $txtFile): void {
        $null   = null;
        $hashes = [];
        $fh     = fopen($txtFile, 'r');
        while (($line = fgets($fh)) !== false) {
            list($a, $b) = explode("\t", "$line\t000000");

            $hash = sha1($a);
            $key  = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
            if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
            $hashes[ $key ][ $hash ] = &$null;

            $hash = sha1($b);
            $key  = substr($hash, 0, self::HASH_FILE_KEY_LENGTH);
            if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
            $hashes[ $key ][ $hash ] = &$null;
        }
        fclose($fh);
        $this->storeHashes($hashes);

        unlink($txtFile);
    }

    /**
     * @param array $hashes
     */
    protected function storeHashes(array $hashes): void {
        foreach ($hashes as $key => $data) {
            $data = json_encode(array_keys($data));
            if(extension_loaded('zlib')) {
                $data = gzcompress($data);
                $this->config->setAppValue(self::CONFIG_DB_ENCODING, self::ENCODING_GZIP);
            } else {
                $this->config->setAppValue(self::CONFIG_DB_ENCODING, self::ENCODING_PLAIN);
            }
            $this->fileCacheService->putFile("$key.json", $data);
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
        $ram = memory_get_peak_usage(true) / 1024 / 1024;
        $this->log->info("Updated local password db. DB: ".static::PASSWORD_DB.", RAM: {$ram}MiB");
    }
}
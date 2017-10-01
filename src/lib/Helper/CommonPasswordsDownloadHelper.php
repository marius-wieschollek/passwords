<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 30.09.17
 * Time: 22:01
 */

namespace OCA\Passwords\Helper;

use Exception;
use OCA\Passwords\Helper\Http\FileDownloadHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use Throwable;

/**
 * Class CommonPasswordsDownloadHelper
 *
 * @package OCA\Passwords\Helper
 */
class CommonPasswordsDownloadHelper {

    const LOW_RAM_LIMIT = 3145728;
    const ARCHIVE_URL   = 'https://archive.org/download/10MillionPasswords/10-million-combos.zip';

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * CommonPasswordsDownloadHelper constructor.
     *
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $configurationService
     */
    public function __construct(FileCacheService $fileCacheService, ConfigurationService $configurationService) {
        $fileCacheService->setDefaultCache($fileCacheService::PASSWORDS_CACHE);
        $this->fileCacheService = $fileCacheService;
        $this->config           = $configurationService;
    }

    /**
     * @return bool
     */
    public function isUpdateRequired() {
        return $this->fileCacheService->isCacheEmpty();
    }

    /**
     *
     */
    public function update() {
        ini_set('memory_limit', -1);
        $zipFile = $this->config->getTempDir().uniqid().'.zip';
        $txtFile = $this->config->getTempDir().uniqid().'.txt';

        $this->downloadPasswordsFile($zipFile);
        $this->unpackPasswordsFile($zipFile, $txtFile);

        if($this->isLowMemorySystem()) {
            $this->lowMemoryHashAlgorithm($txtFile);
        } else {
            $this->highMemoryHashAlgorithm($txtFile);
        }
    }

    /**
     * @param $zipFile
     *
     * @throws Exception
     */
    protected function downloadPasswordsFile($zipFile) {
        $request = new FileDownloadHelper();
        $success = $request
            ->setOutputFile($zipFile)
            ->setUrl(self::ARCHIVE_URL)
            ->sendWithRetry();
        if(!$success) throw new Exception('Failed to download common passwords zip file');
        unset($request);
    }

    /**
     * @param $zipFile
     * @param $txtFile
     *
     * @throws Throwable
     */
    protected function unpackPasswordsFile(string $zipFile, string $txtFile) {
        try {
            $zip = new \ZipArchive;
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
     * This way to create the hashes takes only 180MB of ram.
     * But it also needs 15x the time.
     *
     * @param string $txtFile
     */
    protected function lowMemoryHashAlgorithm(string $txtFile) {
        $null = null;
        for ($i = 0; $i < 16; $i++) {
            $key    = dechex($i);
            $hashes = [];
            $fh     = fopen($txtFile, 'r');
            while (($line = fgets($fh)) !== false) {
                list($a, $b) = explode("\t", $line."\t000000");

                $hash = sha1($a);
                if($hash[0] == $key) $hashes[ $hash ] = &$null;

                $hash = sha1($b);
                if($hash[0] == $key) $hashes[ $hash ] = &$null;
            }
            $this->fileCacheService->putFile("$key.json", json_encode(array_keys($hashes)));
            fclose($fh);
        }
        unlink($txtFile);
    }

    /**
     * This way to create the hashes takes up to 1.7GB of ram.
     * It is also quite fast.
     *
     * @param string $txtFile
     */
    protected function highMemoryHashAlgorithm(string $txtFile) {
        $null   = null;
        $hashes = [];
        $fh     = fopen($txtFile, 'r');
        while (($line = fgets($fh)) !== false) {
            list($a, $b) = explode("\t", $line."\t000000");

            $hash = sha1($a);
            $key  = $hash[0];
            if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
            $hashes[ $key ][ $hash ] = &$null;

            $hash = sha1($b);
            $key  = $hash[0];
            if(!isset($hashes[ $key ])) $hashes[ $key ] = [];
            $hashes[ $key ][ $hash ] = &$null;
        }
        fclose($fh);

        foreach ($hashes as $key => $data) {
            $this->fileCacheService->putFile("$key.json", json_encode(array_keys($data)));
        }
        unlink($txtFile);
    }

    /**
     * @return bool
     */
    protected function isLowMemorySystem(): bool {
        if(preg_match('/MemAvailable:\s+([0-9]+)/', @file_get_contents('/proc/meminfo'), $matches)) {
            return $matches[1] < self::LOW_RAM_LIMIT;
        }

        return true;
    }
}
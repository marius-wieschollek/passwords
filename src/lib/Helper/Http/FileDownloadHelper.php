<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Http;

/**
 * Class FileDownloadHelper
 *
 * @package OCA\Passwords\Helper\Http
 */
class FileDownloadHelper extends RequestHelper {

    const REQUEST_TIMEOUT = 600;

    /**
     * @var string
     */
    protected $file;

    /**
     * @param string $file
     *
     * @return FileDownloadHelper
     */
    public function setOutputFile(string $file): FileDownloadHelper {
        $this->file = $file;

        return $this;
    }

    /**
     * @param string|null $url
     * @param string|null $file
     *
     * @return bool|mixed
     */
    public function send(string $url = null, string $file = null) {
        $curl = $this->prepareCurlRequest($url);

        $fileHandle = fopen($file == null ? $this->file:$file, 'w+');
        curl_setopt($curl, CURLOPT_FILE, $fileHandle);

        curl_exec($curl);
        $this->info = curl_getinfo($curl);

        curl_close($curl);
        fclose($fileHandle);
        unset($curl);
        unset($fileHandle);

        $status = true;
        if(!empty($this->acceptResponseCodes)) $status = in_array($this->info['http_code'], $this->acceptResponseCodes);
        if($status && $this->info['size_download'] != $this->info['download_content_length']) $status = false;

        if(!$status) {
            if(is_file($this->file)) @unlink($this->file);

            return false;
        }

        return true;
    }

    /**
     * @param int|null $retries
     * @param int|null $timeout
     *
     * @return bool|null
     */
    public function sendWithRetry(int $retries = null, int $timeout = null) {
        if($retries === null || $retries < 0) $retries = $this->defaultRetryAttempts;
        if($timeout === null || $timeout < 0) $timeout = $this->defaultRetryTimeout;

        for($i=0; $i<$retries; $i++) {
            if($this->send()) return true;
            if($timeout) sleep($timeout);
        }

        return null;
    }
}
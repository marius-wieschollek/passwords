<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 01.10.17
 * Time: 00:49
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
        $ch = $this->prepareCurlRequest($url);

        $fileHandle = fopen($file == null ? $this->file:$file, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fileHandle);

        curl_exec($ch);
        $this->info = curl_getinfo($ch);

        curl_close($ch);
        fclose($fileHandle);
        unset($ch);
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
     * @param int $maxRetries
     *
     * @return mixed
     */
    public function sendWithRetry($maxRetries = self::REQUEST_MAX_RETRIES) {
        $retries = 0;
        while($retries < $maxRetries) {
            if($this->send()) return true;
            if($this->retryTimeout) sleep($this->retryTimeout);
            $retries++;
        }

        return null;
    }
}
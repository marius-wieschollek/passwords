<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 00:29
 */

namespace OCA\Passwords\Helper;

/**
 * Class HttpRequestHelper
 *
 * @package OCA\Passwords\Helper
 */
class HttpRequestHelper {

    /**
     * @var string
     */
    protected $url;

    /**
     * @param string $url
     *
     * @return HttpRequestHelper
     */
    public function setUrl(string $url): HttpRequestHelper {
        $this->url = $url;

        return $this;
    }

    /**
     * @param string|null $url
     *
     * @return bool|mixed
     */
    public function send(string $url = null) {
        $ch = curl_init($url == null ? $this->url:$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $status   = in_array(curl_getinfo($ch, CURLINFO_HTTP_CODE), ['200', '201', '202']);
        curl_close($ch);

        if(!$status) return false;

        return $response;
    }
}
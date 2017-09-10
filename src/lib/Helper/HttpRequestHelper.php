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
     * @var array
     */
    protected $post;

    /**
     * @var array
     */
    protected $header;

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
     * @param array $post
     *
     * @return HttpRequestHelper
     */
    public function setPost(array $post): HttpRequestHelper {
        $this->post = $post;

        return $this;
    }

    /**
     * @param array $header
     *
     * @return HttpRequestHelper
     */
    public function setHeader(array $header): HttpRequestHelper {
        $this->header = $header;

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

        if(!empty($this->post)) {
            curl_setopt($ch, CURLOPT_POST, 2);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->post));
        }
        if(!empty($this->header)) {
            $header = [];

            foreach ($this->header as $key => $value) {
                $header[] = "{$key}: {$value}";
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $response = curl_exec($ch);
        $status   = in_array(curl_getinfo($ch, CURLINFO_HTTP_CODE), ['200', '201', '202']);
        curl_close($ch);

        if(!$status) return false;

        return $response;
    }

    /**
     * @param int $maxRetries
     *
     * @return mixed
     */
    public function sendWithRetry($maxRetries = 5) {
        $retries = 0;
        while ($retries < $maxRetries) {
            $result = $this->send();

            if($result != null) return $result;
            $retries++;
        }

        return null;
    }
}
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

    const REQUEST_MAX_RETRIES = 5;
    const REQUEST_TIMEOUT     = 15;

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
     * @var array
     */
    protected $json;

    /**
     * @var array
     */
    protected $userAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:55.0) Gecko/20100101 Firefox/55.0';

    /**
     * @var int[]
     */
    protected $acceptResponseCodes = [200, 201, 202];

    /**
     * @var array
     */
    protected $info;

    /**
     * @var string
     */
    protected $response;

    /**
     * HttpRequestHelper constructor.
     *
     * @param string|null $url
     */
    public function __construct(string $url = null) {
        if($url !== null) {
            $this->setUrl($url);
        }
    }

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
     * @param array $json
     *
     * @return HttpRequestHelper
     */
    public function setJson(array $json): HttpRequestHelper {
        $this->json = $json;

        return $this;
    }

    /**
     * @param array $userAgent
     *
     * @return HttpRequestHelper
     */
    public function setUserAgent(array $userAgent): HttpRequestHelper {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @param int[] $acceptResponseCodes
     *
     * @return HttpRequestHelper
     */
    public function setAcceptResponseCodes(array $acceptResponseCodes): HttpRequestHelper {
        $this->acceptResponseCodes = $acceptResponseCodes;

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
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);

        if(!empty($this->post)) {
            curl_setopt($ch, CURLOPT_POST, 2);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->post));
        }
        if(!empty($this->json)) {
            $json = json_encode($this->json);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $this->header['Content-Type']   = 'application/json';
            $this->header['Content-Length'] = strlen($json);
        }
        if(!empty($this->header)) {
            $header = [];

            foreach ($this->header as $key => $value) {
                $header[] = "{$key}: {$value}";
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $this->response = curl_exec($ch);
        $this->info     = curl_getinfo($ch);
        curl_close($ch);

        if(!empty($this->acceptResponseCodes)) {
            $status = in_array($this->info['http_code'], $this->acceptResponseCodes);
            if(!$status) return false;
        }

        return $this->response;
    }

    /**
     * @param int $maxRetries
     *
     * @return mixed
     */
    public function sendWithRetry($maxRetries = self::REQUEST_MAX_RETRIES) {
        $retries = 0;
        while ($retries < $maxRetries) {
            $result = $this->send();

            if($result != null) return $result;
            $retries++;
        }

        return null;
    }

    /**
     * @return array
     */
    public function getInfo(): array {
        return $this->info;
    }

    /**
     * @return mixed
     */
    public function getResponse() {
        return $this->response;
    }
}
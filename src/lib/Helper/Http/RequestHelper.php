<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Http;

use OCA\Passwords\Services\LoggingService;
use OCP\AppFramework\QueryException;

/**
 * Class RequestHelper
 *
 * @package OCA\Passwords\Helper
 */
class RequestHelper {

    const REQUEST_TIMEOUT = 25;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $postData;

    /**
     * @var array
     */
    protected $headerData;

    /**
     * @var array
     */
    protected $jsonData;

    /**
     * @var string
     */
    protected $cookieJar;

    /**
     * @var string
     */
    protected $userAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:66.0) Gecko/20100101 Firefox/66.0';

    /**
     * @var int[]
     */
    protected $acceptResponseCodes = [200, 201, 202];

    /**
     * @var int
     */
    protected $defaultRetryTimeout = 0;

    /**
     * @var int
     */
    protected $defaultRetryAttempts = 5;

    /**
     * @var array
     */
    protected $info;

    /**
     * @var string|false
     */
    protected $error;

    /**
     * @var string
     */
    protected $response;

    /**
     * @var
     */
    protected $responseBody;

    /**
     * @var
     */
    protected $responseHeader;

    /**
     * RequestHelper constructor.
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
     * @return RequestHelper
     */
    public function setUrl(string $url): RequestHelper {
        $this->url = $url;

        return $this;
    }

    /**
     * @param array $post
     *
     * @return RequestHelper
     */
    public function setPostData(array $post): RequestHelper {
        $this->postData = $post;

        return $this;
    }

    /**
     * @param array $header
     *
     * @return RequestHelper
     */
    public function setHeaderData(array $header): RequestHelper {
        $this->headerData = $header;

        return $this;
    }

    /**
     * @param array $json
     *
     * @return RequestHelper
     */
    public function setJsonData(array $json): RequestHelper {
        $this->jsonData = $json;

        return $this;
    }

    /**
     * @param string $userAgent
     *
     * @return RequestHelper
     */
    public function setUserAgent(string $userAgent): RequestHelper {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @param int[] $acceptResponseCodes
     *
     * @return RequestHelper
     */
    public function setAcceptResponseCodes(array $acceptResponseCodes): RequestHelper {
        $this->acceptResponseCodes = $acceptResponseCodes;

        return $this;
    }

    /**
     * @param int $defaultRetryTimeout
     *
     * @return RequestHelper
     */
    public function setDefaultRetryTimeout(int $defaultRetryTimeout): RequestHelper {
        $this->defaultRetryTimeout = $defaultRetryTimeout;

        return $this;
    }

    /**
     * @param int $defaultRetryAttempts
     *
     * @return RequestHelper
     */
    public function setDefaultRetryAttempts(int $defaultRetryAttempts): RequestHelper {
        $this->defaultRetryAttempts = $defaultRetryAttempts;

        return $this;
    }

    /**
     * @param string $cookieJar
     *
     * @return RequestHelper
     */
    public function setCookieJar(string $cookieJar): RequestHelper {
        $this->cookieJar = $cookieJar;

        return $this;
    }

    /**
     * @param string|null $url
     *
     * @return bool|mixed
     */
    public function send(string $url = null) {
        $curl = $this->prepareCurlRequest($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);

        $this->response = curl_exec($curl);
        $this->info     = curl_getinfo($curl);
        $this->error    = curl_error($curl);
        curl_close($curl);

        $headerSize           = $this->info['header_size'];
        $this->responseHeader = substr($this->response, 0, $headerSize);
        $this->responseBody   = substr($this->response, $headerSize);

        if(!empty($this->acceptResponseCodes)) {
            if(!in_array($this->info['http_code'], $this->acceptResponseCodes)) {
                $this->logError();

                return false;
            }
        }

        return $this->responseBody;
    }

    /**
     * @param int|null $retries
     * @param int|null $timeout
     *
     * @return mixed
     */
    public function sendWithRetry(int $retries = null, int $timeout = null) {
        if($retries === null || $retries < 0) $retries = $this->defaultRetryAttempts;
        if($timeout === null || $timeout < 0) $timeout = $this->defaultRetryTimeout;

        for($i = 0; $i < $retries; $i++) {
            $result = $this->send();

            if($result !== false) return $result;
            if($timeout) sleep($timeout);
        }

        return null;
    }

    /**
     * @param string|null $key
     *
     * @return mixed
     */
    public function getInfo(string $key = null) {
        return $key === null ? $this->info:$this->info[ $key ];
    }

    /**
     * @return mixed
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * @return mixed
     */
    public function getResponseHeader() {
        return $this->responseHeader;
    }

    /**
     * @return mixed
     */
    public function getResponseBody() {
        return $this->responseBody;
    }

    /**
     * @return string|false
     */
    public function getError() {
        return $this->error;
    }

    /**
     * @param string $url
     *
     * @return resource
     */
    protected function prepareCurlRequest(string $url = null) {
        $curl = curl_init($url == null ? $this->url:$url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, static::REQUEST_TIMEOUT);

        if(!empty($this->postData)) {
            curl_setopt($curl, CURLOPT_POST, 2);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->postData));
        }

        if(!empty($this->jsonData)) {
            $json = json_encode($this->jsonData);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
            $this->headerData['Content-Type']   = 'application/json';
            $this->headerData['Content-Length'] = strlen($json);
        }

        if(\OC::$server->getConfig()->getSystemValue('proxy')) {
            curl_setopt($curl, CURLOPT_PROXY, \OC::$server->getConfig()->getSystemValue('proxy'));

            if(\OC::$server->getConfig()->getSystemValue('proxyuserpwd')) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, \OC::$server->getConfig()->getSystemValue('proxyuserpwd'));
            }
        }

        if(!empty($this->headerData)) {
            $header = [];

            foreach($this->headerData as $key => $value) {
                $header[] = "{$key}: {$value}";
            }

            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }

        if($this->cookieJar) {
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieJar);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookieJar);
        }

        return $curl;
    }

    /**
     *
     */
    protected function logError() {
        try {
            $logger = \OC::$server->query(LoggingService::class);

            if($this->error !== false && $this->error !== '') {
                $message = sprintf('"%s" when fetching %s', $this->error, $this->info['url']);
            } else {
                $message = sprintf('"Unexpected HTTP %s" when fetching %s (%s redirects)', $this->info['http_code'], $this->info['url'], $this->info['redirect_count']);
            }

            $logger->error($message);
        } catch(QueryException $e) {
        }
    }
}
<?php

namespace OCA\Passwords\Helper\Preview;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\WebsitePreviewService;

/**
 * Class WebshotHelper
 *
 * @package OCA\Passwords\Helper\Preview
 */
class WebshotHelper extends AbstractPreviewHelper {

    const WEBSHOT_DEFAULT_URL = 'http://172.18.0.1:3000/';
    const WEBSHOT_CONFIG_KEY  = 'service/preview/ws/url';

    /**
     * @var string
     */
    protected $prefix = HelperService::PREVIEW_WEBSHOT;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param string $url
     *
     * @return RequestHelper
     */
    protected function getHttpRequest(string $url): RequestHelper {
        $request = parent::getHttpRequest($url);
        $request->setAcceptResponseCodes([200]);
        $request->setDefaultRetryAttempts($this->getMaxRequests());
        $request->setDefaultRetryTimeout(1);

        return $request;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws ApiException
     * @throws \Exception
     */
    protected function getPreviewData(string $domain, string $view): string {
        $url = $this->getPreviewUrl($domain, $view);

        return $this->executeHttpRequest($url);
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws ApiException
     */
    protected function getPreviewUrl(string $domain, string $view): string {
        $serviceUrl = $this->config->getAppValue(self::WEBSHOT_CONFIG_KEY, self::WEBSHOT_DEFAULT_URL);
        $params     = [
            'url'      => $domain,
            'viewport' => ['width' => $view === WebsitePreviewService::VIEWPORT_DESKTOP ? self::WIDTH_DESKTOP:self::WIDTH_MOBILE]
        ];

        $request = parent::getHttpRequest($serviceUrl);
        $request->setDefaultRetryTimeout(3);
        $request->setJsonData($params);
        $data = $request->sendWithRetry();

        if($data === null) {
            $status = $request->getInfo('http_code');
            $this->logger->error("Webshot Request Failed, HTTP {$status}");
            throw new ApiException('API Request Failed', 502);
        }
        $data = json_decode($data);

        return $serviceUrl.$data->id;
    }

    /**
     * @return int
     */
    protected function getMaxRequests(): int {
        $time = intval(ini_get('max_execution_time'));

        return $time <= 0 ? 60:$time - 1;
    }
}
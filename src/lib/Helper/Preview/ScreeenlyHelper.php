<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Preview;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\WebsitePreviewService;

/**
 * Class ScreeenlyHelper
 *
 * @package OCA\Passwords\Helper\Preview
 */
class ScreeenlyHelper extends AbstractPreviewHelper {

    const SCREEENLY_API_CONFIG_KEY = 'service/preview/screeenly/key';

    /**
     * @var string
     */
    protected $prefix = HelperService::PREVIEW_SCREEENLY;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws ApiException
     * @throws \Exception
     */
    protected function getPreviewData(string $domain, string $view): string {
        list($serviceUrl, $serviceParams) = $this->getApiParams($domain, $view);

        $request = $this->getHttpRequest($serviceUrl);
        $request->setDefaultRetryTimeout(3);
        $request->setAcceptResponseCodes([200,400]);
        $request->setJsonData($serviceParams);
        $data = $request->sendWithRetry();

        if($data === null) {
            $status = $request->getInfo('http_code');
            $this->logger->error("Screeenly Request Failed, HTTP {$status}");
            throw new ApiException('API Request Failed', 502);
        }

        $json = json_decode($data);
        if(isset($json->message)) {
            $this->logger->error("Screeenly {$json->title}: {$json->message}");
            throw new ApiException('API Request Failed', 502);
        }

        if(!isset($json->base64_raw)) {
            $this->logger->error("Screeenly did not return an image body");
            throw new ApiException('API Request Failed', 502);
        }

        return base64_decode($json->base64_raw);
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return array
     * @throws ApiException
     */
    protected function getApiParams(string $domain, string $view): array {
        $apiKey = $this->config->getAppValue(self::SCREEENLY_API_CONFIG_KEY);

        $url = 'https://secure.screeenly.com/api/v1';
        if(preg_match('/^https:\/\/(.+)\?key=(\w{50})$/', $apiKey, $matches)) {
            $url    = 'https://'.$matches[1];
            $apiKey = $matches[2];
        }

        if(strlen($apiKey) !== 50) {
            $this->logger->error("Screeenly API key is invalid");
            throw new ApiException('API Request Failed', 502);
        }

        $serviceUrl    = "{$url}/fullsize";
        $serviceParams = [
            'url'   => 'http://'.$domain,
            'key'   => $apiKey,
            'width' => $view === WebsitePreviewService::VIEWPORT_DESKTOP ? self::WIDTH_DESKTOP:self::WIDTH_MOBILE
        ];

        return [$serviceUrl, $serviceParams];
    }
}
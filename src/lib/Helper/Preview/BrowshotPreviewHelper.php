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
 * Class BrowshotPreviewHelper
 *
 * @package OCA\Passwords\Helper\Preview
 */
class BrowshotPreviewHelper extends AbstractPreviewHelper {

    const BWS_API_CONFIG_KEY = 'service/preview/bws/key';

    /**
     * @var string
     */
    protected $prefix = HelperService::PREVIEW_BROW_SHOT;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws ApiException
     */
    protected function getPreviewUrl(string $domain, string $view): string {
        $apiKey    = $this->config->getAppValue(self::BWS_API_CONFIG_KEY);
        $createUrl = $this->getCreateUrl($apiKey, $domain, $view);

        $request  = parent::getHttpRequest($createUrl);
        $response = $request->send();

        if($response === null) {
            $status = $request->getInfo('http_code');
            $this->logger->error("Browshot Request Failed, HTTP {$status}");
            throw new ApiException('API Request Failed', 502);
        }

        $data = json_decode($response);
        return $this->waitForResult($data, $apiKey);
    }

    /**
     * @param \stdClass $data
     * @param string    $apiKey
     *
     * @return string
     * @throws ApiException
     */
    protected function waitForResult(\stdClass $data, string $apiKey): string {
        $infoUrl = $this->getInfoUrl($data->id, $apiKey);

        while(in_array($data->status, ['in_queue', 'in_process', 'processing'])) {
            sleep(1);
            $request  = parent::getHttpRequest($infoUrl);
            $response = $request->send();

            if($response === null) {
                $status = $request->getInfo('http_code');
                $this->logger->error("Browshot Request Failed, HTTP {$status}");
                throw new ApiException('API Request Failed', 502);
            }

            $data = json_decode($response);
        }

        if($data->status === 'error') {
            $this->logger->error("Browshot Request Failed, Error {$data->error}");
            throw new ApiException('API Request Failed', 502);
        }

        return $data->screenshot_url;
    }

    /**
     * @param string $apiKey
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getCreateUrl(string $apiKey, string $domain, string $view): string {
        if($view === WebsitePreviewService::VIEWPORT_DESKTOP) {
            return "https://api.browshot.com/api/v1/screenshot/create?key={$apiKey}&url={$domain}&instance_id=27&size=page";
        }

        return "https://api.browshot.com/api/v1/screenshot/create?key={$apiKey}&url={$domain}&instance_id=67&size=page";
    }

    /**
     * @param string $id
     * @param string $apiKey
     *
     * @return string
     */
    protected function getInfoUrl(string $id, string $apiKey): string {
        return "https://api.browshot.com/api/v1/screenshot/info?id={$id}&key={$apiKey}&details=1";
    }
}
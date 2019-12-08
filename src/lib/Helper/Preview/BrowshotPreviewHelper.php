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

    const BWS_API_CONFIG_KEY   = 'service/preview/bws/key';
    const BWS_MOBILE_INSTANCE  = 'service/preview/bws/mobile';
    const BWS_DESKTOP_INSTANCE = 'service/preview/bws/desktop';

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
        $data      = $this->sendApiRequest($createUrl);

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
            $data = $this->sendApiRequest($infoUrl);
        }

        if($data->status === 'error') {
            $this->logger->error("Browshot Request Failed, {$data->error}");
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
     * @throws ApiException
     */
    protected function getCreateUrl(string $apiKey, string $domain, string $view): string {
        $createUrl = "https://api.browshot.com/api/v1/screenshot/create?key={$apiKey}&url={$domain}&size=page&instance_id=";
        $info      = $this->sendApiRequest("https://api.browshot.com/api/v1/account/info?key={$apiKey}");

        if(intval($info->free_screenshots_left) > 0) {
            return $createUrl.($view === WebsitePreviewService::VIEWPORT_DESKTOP ? '27':'67');
        }

        $balance = intval($info->balance);
        if($balance > 0 && $view === WebsitePreviewService::VIEWPORT_DESKTOP) {
            $instance = $this->config->getAppValue(self::BWS_DESKTOP_INSTANCE, '58');

            return $createUrl.$instance.'&screen_width=1600&screen_height=1200';
        }

        if($balance > 1 && $view === WebsitePreviewService::VIEWPORT_MOBILE) {
            $instance = $this->config->getAppValue(self::BWS_MOBILE_INSTANCE, '275');

            return $createUrl.$instance;
        }

        $this->logger->error("Insufficient Browshot Account Balance");
        throw new ApiException('API Request Failed', 502);
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

    /**
     * @param $url
     *
     * @return mixed
     * @throws ApiException
     */
    protected function sendApiRequest($url) {
        $request  = parent::getHttpRequest($url);
        $response = $request->send();

        if($response === null) {
            $status = $request->getInfo('http_code');
            $this->logger->error("Browshot Request Failed, HTTP {$status}");
            throw new ApiException('API Request Failed', 502);
        }

        return json_decode($response);
    }
}
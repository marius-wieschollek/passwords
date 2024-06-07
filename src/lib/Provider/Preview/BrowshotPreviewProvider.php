<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Provider\Preview;

use Exception;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\WebsitePreviewService;
use OCP\AppFramework\Http;
use stdClass;

/**
 * Class BrowshotPreviewProvider
 *
 * @package OCA\Passwords\Helper\Preview
 */
class BrowshotPreviewProvider extends AbstractPreviewProvider {

    const BWS_API_CONFIG_KEY   = 'service/preview/bws/key';
    const BWS_MOBILE_INSTANCE  = 'service/preview/bws/mobile';
    const BWS_DESKTOP_INSTANCE = 'service/preview/bws/desktop';

    /**
     * @var string
     */
    protected string $prefix = HelperService::PREVIEW_BROW_SHOT;

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
     * @param stdClass $data
     * @param string    $apiKey
     *
     * @return string
     * @throws ApiException
     */
    protected function waitForResult(stdClass $data, string $apiKey): string {
        $infoUrl = $this->getInfoUrl($data->id, $apiKey);

        while(in_array($data->status, ['in_queue', 'in_process', 'processing'])) {
            sleep(1);
            $data = $this->sendApiRequest($infoUrl);
        }

        if($data->status === 'error') {
            $this->loggingService->error("Browshot Request Failed, {$data->error}");
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY);
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
        $createUrl = "https://api.browshot.com/api/v1/screenshot/create?key={$apiKey}&url={$domain}&size=page&delay=2&instance_id=";
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

        $this->loggingService->error("Insufficient Browshot Account Balance");
        throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY);
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
        $client = $this->httpClientService->newClient();

        try {
            $response = $client->get($url);

            return json_decode($response->getBody());
        } catch(Exception $e) {
            $this->loggingService->error("Browshot Request Failed, HTTP {$e->getCode()}");
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY);
        }
    }
}
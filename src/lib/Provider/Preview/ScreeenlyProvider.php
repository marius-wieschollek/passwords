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

use GuzzleHttp\Exception\ClientException;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\WebsitePreviewService;
use OCP\AppFramework\Http;
use Throwable;

/**
 * Class ScreeenlyProvider
 *
 * @package OCA\Passwords\Helper\Preview
 */
class ScreeenlyProvider extends AbstractPreviewProvider {

    const SCREEENLY_API_CONFIG_KEY = 'service/preview/screeenly/key';

    /**
     * @var string
     */
    protected string $prefix = HelperService::PREVIEW_SCREEENLY;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws ApiException
     */
    protected function getPreviewData(string $domain, string $view): string {
        [$serviceUrl, $serviceParams] = $this->getApiParams($domain, $view);

        try {
            $client   = $this->httpClientService->newClient();
            $response = $client->post($serviceUrl, ['json' => $serviceParams]);
        } catch(Throwable $e) {
            $code = $e instanceof ClientException ? "HTTP {$e->getResponse()->getStatusCode()}":$e->getMessage();
            $this->loggingService->error("Screeenly Request Failed, HTTP {$code}");
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY, $e);
        }

        $data = $response->getBody();
        if($data === null) {
            $this->loggingService->error("Screeenly Request Failed, HTTP {$response->getStatusCode()}");
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY);
        }

        $json = json_decode($data);
        if(isset($json->message)) {
            $this->loggingService->error("Screeenly {$json->title}: {$json->message}");
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY);
        }

        if(!isset($json->base64_raw)) {
            $this->loggingService->error("Screeenly did not return an image body");
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY);
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
        if(preg_match('/^(http|https):\/\/(.+)\?key=(\w{50})$/', $apiKey, $matches)) {
            $url    = $matches[1].'://'.$matches[2];
            $apiKey = $matches[3];
        }

        if(strlen($apiKey) !== 50) {
            $this->loggingService->error("Screeenly API key is invalid");
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY);
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

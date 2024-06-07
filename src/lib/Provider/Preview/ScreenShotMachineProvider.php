<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Provider\Preview;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\WebsitePreviewService;
use OCP\AppFramework\Http;

/**
 * Class ScreenShotMachineProvider
 *
 * @package OCA\Passwords\Helper\Preview
 */
class ScreenShotMachineProvider extends AbstractPreviewProvider {

    const SSM_API_CONFIG_KEY = 'service/preview/ssm/key';

    const SSM_ERROR_IMAGES
        = [
            'de2b58db567799c57939e7c037ac5c7177b7d000' => 'Invalid URL',
            '15fbaa9ab38c202d88f6d154cd3406323cfffb0b' => 'Invalid key'
        ];

    /**
     * @var string
     */
    protected string $prefix = HelperService::PREVIEW_SCREEN_SHOT_MACHINE;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPreviewUrl(string $domain, string $view): string {
        $apiKey = $this->config->getAppValue(self::SSM_API_CONFIG_KEY);

        if($view === WebsitePreviewService::VIEWPORT_DESKTOP) {
            return "https://api.screenshotmachine.com/?key={$apiKey}&dimension=".self::WIDTH_DESKTOP."xfull&device=desktop&format=jpg&url={$domain}&delay=600";
        }

        return "https://api.screenshotmachine.com/?key={$apiKey}&dimension=".self::WIDTH_MOBILE."xfull&device=phone&format=jpg&url={$domain}&delay=600";
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws ApiException
     */
    protected function executeHttpRequest(string $url): string {
        $result = parent::executeHttpRequest($url);

        $sha1 = sha1($result);
        if(isset(self::SSM_ERROR_IMAGES[ $sha1 ])) {
            $this->loggingService->error('Screenshotmachine: '.self::SSM_ERROR_IMAGES[ $sha1 ], ['requestUrl' => $url]);
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY);
        }

        return $result;
    }
}
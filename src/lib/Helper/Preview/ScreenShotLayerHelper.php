<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Preview;

use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\WebsitePreviewService;

/**
 * Class ScreenShotLayerHelper
 *
 * @package OCA\Passwords\Helper\PageShot
 */
class ScreenShotLayerHelper extends AbstractPreviewHelper {

    const SSL_API_CONFIG_KEY = 'service/preview/ssl/key';

    /**
     * @var string
     */
    protected $prefix = HelperService::PREVIEW_SCREEN_SHOT_LAYER;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPreviewUrl(string $domain, string $view): string {
        $apiKey = $this->config->getAppValue(self::SSL_API_CONFIG_KEY);

        if($view === WebsitePreviewService::VIEWPORT_DESKTOP) {
            return "http://api.screenshotlayer.com/api/capture?access_key={$apiKey}&viewport=".self::VIEWPORT_DESKTOP."&width=720&fullpage=1&url=http://{$domain}";
        }

        return "http://api.screenshotlayer.com/api/capture?access_key={$apiKey}&viewport=".self::VIEWPORT_MOBILE."&width=720&fullpage=1&url=http://{$domain}";
    }
}
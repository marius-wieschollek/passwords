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
 * Class ScreenShotMachineHelper
 *
 * @package OCA\Passwords\Helper\Preview
 */
class ScreenShotMachineHelper extends AbstractPreviewHelper {

    const SSM_API_CONFIG_KEY = 'service/preview/ssm/key';

    /**
     * @var string
     */
    protected $prefix = HelperService::PREVIEW_SCREEN_SHOT_MACHINE;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPreviewUrl(string $domain, string $view): string {
        $apiKey = $this->config->getAppValue(self::SSM_API_CONFIG_KEY);

        if($view === WebsitePreviewService::VIEWPORT_DESKTOP) {
            return "http://api.screenshotmachine.com/?key={$apiKey}&dimension=".self::WIDTH_DESKTOP."xfull&device=desktop&format=jpg&url={$domain}";
        }

        return "http://api.screenshotmachine.com/?key={$apiKey}&dimension=".self::WIDTH_MOBILE."xfull&device=phone&format=jpg&url={$domain}";
    }
}
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

use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\WebsitePreviewService;

/**
 * Class ScreenShotMachineProvider
 *
 * @package OCA\Passwords\Helper\Preview
 */
class ScreenShotMachineProvider extends AbstractPreviewProvider {

    const SSM_API_CONFIG_KEY = 'service/preview/ssm/key';

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
}
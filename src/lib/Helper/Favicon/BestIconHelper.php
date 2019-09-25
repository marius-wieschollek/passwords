<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Helper\Icon\FallbackIconGenerator;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;

/**
 * Class BetterIdeaHelper
 */
class BestIconHelper extends AbstractFaviconHelper {

    const BESTICON_DEFAULT_URL = 'https://passwords-app-favicons.herokuapp.com/icon';
    const BESTICON_CONFIG_KEY  = 'service/favicon/bi/url';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var string
     */
    protected $prefix = HelperService::FAVICON_BESTICON;

    /**
     * BestIconHelper constructor.
     *
     * @param ConfigurationService  $config
     * @param HelperService         $helperService
     * @param FileCacheService      $fileCacheService
     * @param FallbackIconGenerator $fallbackIconGenerator
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        ConfigurationService $config,
        HelperService $helperService,
        FileCacheService $fileCacheService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->config = $config;
        parent::__construct($helperService, $fileCacheService, $fallbackIconGenerator);
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain): string {
        $fallbackColor = substr($this->fallbackIconGenerator->stringToColor($domain), 1);
        $serviceUrl    = $this->config->getAppValue(self::BESTICON_CONFIG_KEY, self::BESTICON_DEFAULT_URL);
        if($serviceUrl === self::BESTICON_DEFAULT_URL) $serviceUrl = $this->getSharedInstanceUrl();

        return "{$serviceUrl}?size=16..128..256&fallback_icon_color={$fallbackColor}&url={$domain}";
    }

    /**
     * @return string
     */
    protected function getSharedInstanceUrl(): string {
        $user = $this->config->getSystemValue('instanceid');
        $password = sha1($user.'ncpw');

        return "https://{$user}:{$password}@ncpw.mdns.eu/icon";
    }
}
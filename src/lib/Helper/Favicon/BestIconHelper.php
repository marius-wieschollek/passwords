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
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;

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
     * @param IClientService        $requestService
     * @param FileCacheService      $fileCacheService
     * @param FallbackIconGenerator $fallbackIconGenerator
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        ConfigurationService $config,
        HelperService $helperService,
        IClientService $requestService,
        FileCacheService $fileCacheService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->config = $config;
        parent::__construct($helperService, $requestService, $fileCacheService, $fallbackIconGenerator);
    }

    /**
     * @param string $domain
     *
     * @return array
     */
    protected function getRequestData(string $domain): array {
        $fallbackColor = substr($this->fallbackIconGenerator->stringToColor($domain), 1);
        $options       = [
            'query'   => [
                'size'                => '16..128..256',
                'fallback_icon_color' => $fallbackColor,
                'url'                 => $domain,
            ]
        ];

        $serviceUrl = $this->config->getAppValue(self::BESTICON_CONFIG_KEY, self::BESTICON_DEFAULT_URL);
        if($serviceUrl === self::BESTICON_DEFAULT_URL || empty($serviceUrl)) {
            return $this->getSharedInstanceUrl($options);
        }

        return [$serviceUrl, $options];
    }

    /**
     * @return array
     */
    protected function getSharedInstanceUrl($options): array {
        $user            = $this->config->getSystemValue('instanceid');
        $password        = sha1($user.'ncpw');
        $options['auth'] = [$user, $password];

        return ["https://{$user}:{$password}@ncpw.mdns.eu/icon", $options];
    }
}
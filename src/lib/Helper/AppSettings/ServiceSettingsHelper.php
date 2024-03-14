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

namespace OCA\Passwords\Helper\AppSettings;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Provider\Favicon\BestIconProvider;
use OCA\Passwords\Provider\Preview\BrowshotPreviewProvider;
use OCA\Passwords\Provider\Preview\ScreeenlyProvider;
use OCA\Passwords\Provider\Preview\ScreenShotLayerProvider;
use OCA\Passwords\Provider\Preview\ScreenShotMachineProvider;
use OCA\Passwords\Provider\SecurityCheck\BigLocalDbSecurityCheckProvider;
use OCA\Passwords\Provider\SecurityCheck\HaveIBeenPwnedProvider;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;
use OCP\AppFramework\Http;
use OCP\IL10N;

/**
 * Class ServiceSettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class ServiceSettingsHelper extends AbstractSettingsHelper {

    /**
     * @var IL10N
     */
    protected IL10N $localisation;

    /**
     * @var HelperService
     */
    protected HelperService $helperService;

    /**
     * @var string
     */
    protected string $scope = 'service';

    /**
     * @var array
     */
    protected array $keys
        = [
            'security'           => 'service/security',
            'words'              => 'service/words',
            'images'             => 'service/images',
            'preview'            => 'service/preview',
            'favicon'            => 'service/favicon',
            'security.hibp.url'  => HaveIBeenPwnedProvider::CONFIG_SERVICE_URL,
            'security.local.url' => BigLocalDbSecurityCheckProvider::CONFIG_DB_SOURCE
        ];

    /**
     * @var array
     */
    protected array $defaults
        = [
            'security'           => HelperService::SECURITY_HIBP,
            'preview'            => HelperService::PREVIEW_DEFAULT,
            'favicon'            => HelperService::FAVICON_DEFAULT,
            'words'              => HelperService::WORDS_AUTO,
            'images'             => HelperService::IMAGES_AUTO,
            'preview.api'        => '',
            'favicon.api'        => '',
            'security.hibp.url'  => null,
            'security.local.url' => null
        ];

    /**
     * @var array
     */
    protected array $types
        = [
            'preview.api'        => 'string',
            'favicon.api'        => 'string',
            'security.hibp.url'  => 'string',
            'security.local.url' => 'string'
        ];

    /**
     * @var array
     */
    protected array $depends
        = [
            'preview.api'        =>
                [
                    'service.preview' => [
                        HelperService::PREVIEW_SCREEENLY,
                        HelperService::PREVIEW_BROW_SHOT,
                        HelperService::PREVIEW_SCREEN_SHOT_LAYER,
                        HelperService::PREVIEW_SCREEN_SHOT_MACHINE
                    ]
                ],
            'favicon.api'        =>
                [
                    'service.favicon' => [HelperService::FAVICON_BESTICON]
                ],
            'security.hibp.url'  =>
                [
                    'service.security' => [
                        HelperService::SECURITY_HIBP,
                        HelperService::SECURITY_BIGDB_HIBP
                    ]
                ],
            'security.local.url' =>
                [
                    'service.favicon' => [
                        HelperService::SECURITY_BIG_LOCAL,
                        HelperService::SECURITY_SMALL_LOCAL,
                        HelperService::SECURITY_BIGDB_HIBP
                    ]
                ],
        ];

    /**
     * ServiceSettingsHelper constructor.
     *
     * @param ConfigurationService $config
     * @param HelperService        $helperService
     * @param IL10N                $localisation
     */
    public function __construct(ConfigurationService $config, HelperService $helperService, IL10N $localisation) {
        parent::__construct($config);
        $this->localisation  = $localisation;
        $this->helperService = $helperService;
    }

    /**
     * @return array
     */
    public function list(): array {
        try {
            return [
                $this->get('words'),
                $this->get('images'),
                $this->get('favicon'),
                $this->get('preview'),
                $this->get('security'),
                $this->get('favicon.api'),
                $this->get('preview.api'),
                $this->get('security.hibp.url'),
                $this->get('security.local.url')
            ];
        } catch(ApiException $e) {
            return [];
        }
    }

    /**
     * @param $key
     *
     * @return array
     * @throws ApiException
     */
    public function get(string $key): array {
        switch($key) {
            case 'words':
            case 'images':
            case 'favicon':
            case 'preview':
            case 'security':
            case 'favicon.api':
            case 'preview.api':
            case 'security.hibp.url':
            case 'security.local.url':
                return $this->getGenericSetting($key);
        }

        throw new ApiException('Unknown setting identifier', Http::STATUS_BAD_REQUEST);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function getFaviconApiKey(): string {
        $service = $this->config->getAppValue('service/favicon', HelperService::FAVICON_DEFAULT);

        if($service === HelperService::FAVICON_BESTICON) {
            return BestIconProvider::BESTICON_CONFIG_KEY;
        }

        return 'service/favicon/api';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function getPreviewApiKey(): string {
        $service = $this->config->getAppValue('service/preview', HelperService::PREVIEW_DEFAULT);

        if($service === HelperService::PREVIEW_SCREEN_SHOT_LAYER) {
            return ScreenShotLayerProvider::SSL_API_CONFIG_KEY;
        }

        if($service === HelperService::PREVIEW_SCREEN_SHOT_MACHINE) {
            return ScreenShotMachineProvider::SSM_API_CONFIG_KEY;
        }

        if($service === HelperService::PREVIEW_BROW_SHOT) {
            return BrowshotPreviewProvider::BWS_API_CONFIG_KEY;
        }

        if($service === HelperService::PREVIEW_SCREEENLY) {
            return ScreeenlyProvider::SCREEENLY_API_CONFIG_KEY;
        }

        return 'service/preview/api';
    }

    /**
     * @return array
     */
    protected function getSecurityOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::SECURITY_HIBP,
                $this->localisation->t('Have I been pwned? (recommended)'),
                $this->helperService->getSecurityHelper(HelperService::SECURITY_HIBP)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::SECURITY_BIG_LOCAL,
                $this->localisation->t('Big local database (25M passwords)'),
                $this->helperService->getSecurityHelper(HelperService::SECURITY_BIG_LOCAL)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::SECURITY_SMALL_LOCAL,
                $this->localisation->t('Small local database (5M passwords)'),
                $this->helperService->getSecurityHelper(HelperService::SECURITY_SMALL_LOCAL)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::SECURITY_BIGDB_HIBP,
                $this->localisation->t('Big local database & Hibp?'),
                $this->helperService->getSecurityHelper(HelperService::SECURITY_BIGDB_HIBP)->isAvailable()
            )
        ];
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    protected function getWordsOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::WORDS_AUTO,
                $this->localisation->t('Select automatically (recommended)'),
                $this->helperService->getWordsHelper(HelperService::WORDS_AUTO)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::WORDS_LEIPZIG,
                $this->localisation->t('Leipzig Corpora Collection'),
                $this->helperService->getWordsHelper(HelperService::WORDS_LEIPZIG)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::WORDS_LOCAL,
                $this->localisation->t('Local dictionary'),
                $this->helperService->getWordsHelper(HelperService::WORDS_LOCAL)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::WORDS_SNAKES,
                $this->localisation->t('watchout4snakes.com'),
                $this->helperService->getWordsHelper(HelperService::WORDS_SNAKES)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::WORDS_RANDOM,
                $this->localisation->t('Random Characters'),
                $this->helperService->getWordsHelper(HelperService::WORDS_RANDOM)->isAvailable()
            )
        ];
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    protected function getFaviconOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::FAVICON_LOCAL,
                $this->localisation->t('Local analyzer')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_BESTICON,
                $this->localisation->t('Besticon (recommended)')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_FAVICON_GRABBER,
                $this->localisation->t('favicongrabber.com')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_DUCK_DUCK_GO,
                $this->localisation->t('DuckDuckGo')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_GOOGLE,
                $this->localisation->t('Google')
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_DEFAULT,
                $this->localisation->t('None')
            )
        ];
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    protected function getPreviewOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::PREVIEW_PAGERES,
                $this->localisation->t('Pageres CLI (Local)')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_BROW_SHOT,
                $this->localisation->t('Browshot')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_SCREEENLY,
                $this->localisation->t('screeenly')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_SCREEN_SHOT_LAYER,
                $this->localisation->t('screenshotlayer')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                $this->localisation->t('screenshotmachine.com')
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_DEFAULT,
                $this->localisation->t('None')
            )
        ];
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    protected function getImagesOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::IMAGES_AUTO,
                $this->localisation->t('Select automatically (recommended)'),
                $this->helperService->getImageHelper(HelperService::IMAGES_AUTO)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::IMAGES_IMAGICK,
                $this->localisation->t('Imagick/GMagick'),
                $this->helperService->getImageHelper(HelperService::IMAGES_IMAGICK)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::IMAGES_IMAGINARY,
                $this->localisation->t('Imaginary'),
                $this->helperService->getImageHelper(HelperService::IMAGES_IMAGINARY)->isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::IMAGES_GDLIB,
                $this->localisation->t('PHP GDLib'),
                $this->helperService->getImageHelper(HelperService::IMAGES_GDLIB)->isAvailable()
            )
        ];
    }
}
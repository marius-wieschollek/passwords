<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 18:12
 */

namespace OCA\Passwords\Settings;

use Gmagick;
use Imagick;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Favicon\BestIconHelper;
use OCA\Passwords\Helper\Preview\ScreenShotApiHelper;
use OCA\Passwords\Helper\Preview\ScreenShotMachineHelper;
use OCA\Passwords\Helper\Preview\WkhtmlImageHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\ISettings;

/**
 * Class AdminSettings
 *
 * @package OCA\Passwords\Settings
 */
class AdminSettings implements ISettings {

    const DOCUMENTATION_URL = 'https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/Administrative-Settings';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var IL10N
     */
    protected $localisation;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * AdminSettings constructor.
     *
     * @param IL10N                $localisation
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     */
    public function __construct(IL10N $localisation, ConfigurationService $config, FileCacheService $fileCacheService) {
        $this->localisation     = $localisation;
        $this->config           = $config;
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
     */
    public function getForm(): TemplateResponse {
        return new TemplateResponse('passwords', 'admin/index', [
            'documentationUrl' => self::DOCUMENTATION_URL,
            'imageServices'    => $this->getImageServices(),
            'wordsServices'    => $this->getWordsServices(),
            'faviconServices'  => $this->getFaviconServices(),
            'previewServices'  => $this->getWebsitePreviewServices(),
            'securityServices' => $this->getSecurityServices(),
            'legacyApiEnabled' => $this->config->getAppValue('legacy_api_enabled', true),
            'legacyLastUsed'   => $this->config->getAppValue('legacy_last_used', null),
            'caches'           => $this->getFileCaches()
        ]);
    }

    /**
     * @return array
     */
    protected function getSecurityServices(): array {
        $current = $this->config->getAppValue('service/security', HelperService::SECURITY_HIBP);

        return [
            [
                'id'      => HelperService::SECURITY_HIBP,
                'label'   => $this->localisation->t('Have I been pwned? (recommended)'),
                'current' => $current === HelperService::SECURITY_HIBP
            ],
            [
                'id'      => HelperService::SECURITY_BIG_LOCAL,
                'label'   => $this->localisation->t('10 Million Passwords (Local)'),
                'current' => $current === HelperService::SECURITY_BIG_LOCAL
            ],
            [
                'id'      => HelperService::SECURITY_SMALL_LOCAL,
                'label'   => $this->localisation->t('1 Million Passwords (Local)'),
                'current' => $current === HelperService::SECURITY_SMALL_LOCAL
            ],
            [
                'id'      => HelperService::SECURITY_BIGDB_HIBP,
                'label'   => $this->localisation->t('10Mio Passwords & Hibp?'),
                'current' => $current === HelperService::SECURITY_BIGDB_HIBP
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getWordsServices(): array {
        $current = $this->config->getAppValue('service/words', HelperService::WORDS_RANDOM);

        return [
            [
                'id'      => HelperService::WORDS_LOCAL,
                'label'   => $this->localisation->t('Local dictionary'),
                'current' => $current === HelperService::WORDS_LOCAL
            ],
            [
                'id'      => HelperService::WORDS_SNAKES,
                'label'   => $this->localisation->t('watchout4snakes.com (recommended)'),
                'current' => $current === HelperService::WORDS_SNAKES
            ],
            [
                'id'      => HelperService::WORDS_RANDOM,
                'label'   => $this->localisation->t('Random Characters'),
                'current' => $current === HelperService::WORDS_RANDOM
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getImageServices(): array {
        $current = $this->config->getAppValue('service/images', HelperService::IMAGES_IMAGICK);

        if($current == HelperService::IMAGES_IMAGICK && !class_exists(Imagick::class) && !class_exists(Gmagick::class)) {
            $current = HelperService::IMAGES_GDLIB;
        }

        return [
            [
                'id'      => HelperService::IMAGES_IMAGICK,
                'label'   => $this->localisation->t('Imagick/GMagick (recommended)'),
                'current' => $current === HelperService::IMAGES_IMAGICK
            ],
            [
                'id'      => HelperService::IMAGES_GDLIB,
                'label'   => $this->localisation->t('PHP GDLib'),
                'current' => $current === HelperService::IMAGES_GDLIB
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getFaviconServices(): array {
        $current = $this->config->getAppValue('service/favicon', HelperService::FAVICON_DEFAULT);

        return [
            [
                'id'      => HelperService::FAVICON_LOCAL,
                'label'   => $this->localisation->t('Local analyzer'),
                'current' => $current === HelperService::FAVICON_LOCAL,
                'api'     => null
            ],
            [
                'id'      => HelperService::FAVICON_BESTICON,
                'label'   => $this->localisation->t('Besticon (recommended)'),
                'current' => $current === HelperService::FAVICON_BESTICON,
                'api'     => [
                    'key'   => BestIconHelper::BESTICON_CONFIG_KEY,
                    'value' => $this->config->getAppValue(BestIconHelper::BESTICON_CONFIG_KEY, BestIconHelper::BESTICON_DEFAULT_URL)
                ]
            ],
            [
                'id'      => HelperService::FAVICON_DUCK_DUCK_GO,
                'label'   => $this->localisation->t('DuckDuckGo'),
                'current' => $current === HelperService::FAVICON_DUCK_DUCK_GO,
                'api'     => null
            ],
            [
                'id'      => HelperService::FAVICON_GOOGLE,
                'label'   => $this->localisation->t('Google'),
                'current' => $current === HelperService::FAVICON_GOOGLE,
                'api'     => null
            ],
            [
                'id'      => HelperService::FAVICON_DEFAULT,
                'label'   => $this->localisation->t('None'),
                'current' => $current === HelperService::FAVICON_DEFAULT,
                'api'     => null
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getWebsitePreviewServices(): array {
        $current = $this->config->getAppValue('service/preview', HelperService::PREVIEW_DEFAULT);

        return [
            [
                'id'      => HelperService::PREVIEW_WKHTML,
                'label'   => $this->localisation->t('WKHTML (Local)'),
                'current' => $current === HelperService::PREVIEW_WKHTML,
                'path'    => WkhtmlImageHelper::getWkhtmlPath(),
                'api'     => null
            ],
            [
                'id'      => HelperService::PREVIEW_PAGERES,
                'label'   => $this->localisation->t('Pageres/PhantomJS (Local)'),
                'current' => $current === HelperService::PREVIEW_PAGERES,
                'api'     => null
            ],
            [
                'id'      => HelperService::PREVIEW_SCREEN_SHOT_API,
                'label'   => $this->localisation->t('screenshotapi.io'),
                'current' => $current === HelperService::PREVIEW_SCREEN_SHOT_API,
                'api'     => [
                    'key'   => ScreenShotApiHelper::SSA_API_CONFIG_KEY,
                    'value' => $this->config->getAppValue(ScreenShotApiHelper::SSA_API_CONFIG_KEY)
                ]
            ],
            [
                'id'      => HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                'label'   => $this->localisation->t('screenshotmachine.com'),
                'current' => $current === HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                'api'     => [
                    'key'   => ScreenShotMachineHelper::SSM_API_CONFIG_KEY,
                    'value' => $this->config->getAppValue(ScreenShotMachineHelper::SSM_API_CONFIG_KEY)
                ]
            ],
            [
                'id'      => HelperService::PREVIEW_DEFAULT,
                'label'   => $this->localisation->t('None'),
                'current' => $current === HelperService::PREVIEW_DEFAULT,
                'api'     => null
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getFileCaches(): array {
        $caches = $this->fileCacheService->listCaches();

        $info = [];
        foreach($caches as $cache) {
            try {
                $info[] = $this->fileCacheService->getCacheInfo($cache);
            } catch(\Exception $e) {
            }
        }

        return $info;
    }

    /**
     * @return string the section ID, e.g. 'sharing'
     */
    public function getSection(): string {
        return Application::APP_NAME;
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the admin section. The forms are arranged in ascending order of the
     * priority values. It is required to return a value between 0 and 100.
     */
    public function getPriority(): int {
        return 0;
    }
}
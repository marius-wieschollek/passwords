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
use OCA\Passwords\Helper\PageShot\WkhtmlImageHelper;
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
    public function getForm() {

        return new TemplateResponse('passwords', 'admin/index', [
            'imageServices'    => $this->getImageServices(),
            'wordsServices'    => $this->getWordsServices(),
            'faviconServices'  => $this->getFaviconServices(),
            'pageshotServices' => $this->getPageShotServices(),
            'securityServices' => $this->getSecurityServices(),
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
                'label'   => $this->localisation->t('Local'),
                'current' => $current === HelperService::WORDS_LOCAL
            ],
            [
                'id'      => HelperService::WORDS_RANDOM,
                'label'   => $this->localisation->t('Random Characters'),
                'current' => $current === HelperService::WORDS_RANDOM
            ],
            [
                'id'      => HelperService::WORDS_SNAKES,
                'label'   => $this->localisation->t('watchout4snakes.com (recommended)'),
                'current' => $current === HelperService::WORDS_SNAKES
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
                'label'   => $this->localisation->t('Local'),
                'current' => $current === HelperService::FAVICON_LOCAL
            ],
            [
                'id'      => HelperService::FAVICON_BETTER_IDEA,
                'label'   => $this->localisation->t('The Favicon Finder (recommended)'),
                'current' => $current === HelperService::FAVICON_BETTER_IDEA
            ],
            [
                'id'      => HelperService::FAVICON_DUCK_DUCK_GO,
                'label'   => $this->localisation->t('DuckDuckGo'),
                'current' => $current === HelperService::FAVICON_DUCK_DUCK_GO
            ],
            [
                'id'      => HelperService::FAVICON_GOOGLE,
                'label'   => $this->localisation->t('google.com'),
                'current' => $current === HelperService::FAVICON_GOOGLE
            ],
            [
                'id'      => HelperService::FAVICON_DEFAULT,
                'label'   => $this->localisation->t('None'),
                'current' => $current === HelperService::FAVICON_DEFAULT
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getPageShotServices(): array {
        $current = $this->config->getAppValue('service/pageshot', HelperService::PAGESHOT_DEFAULT);

        return [
            [
                'id'      => HelperService::PAGESHOT_DEFAULT,
                'label'   => $this->localisation->t('None'),
                'current' => $current === HelperService::PAGESHOT_DEFAULT,
                'api'     => null
            ],
            [
                'id'      => HelperService::PAGESHOT_WKHTML,
                'label'   => $this->localisation->t('WKHTML (local)'),
                'current' => $current === HelperService::PAGESHOT_WKHTML,
                'path'    => WkhtmlImageHelper::getWkhtmlPath(),
                'api'     => null
            ],
            [
                'id'      => HelperService::PAGESHOT_SCREEN_SHOT_API,
                'label'   => $this->localisation->t('screenshotapi.io (recommended)'),
                'current' => $current === HelperService::PAGESHOT_SCREEN_SHOT_API,
                'api'     => [
                    'key'   => 'service/pageshot/ssa/key',
                    'value' => $this->config->getAppValue('service/pageshot/ssa/key')
                ]
            ],
            [
                'id'      => HelperService::PAGESHOT_SCREEN_SHOT_LAYER,
                'label'   => $this->localisation->t('screenshotlayer.com'),
                'current' => $current === HelperService::PAGESHOT_SCREEN_SHOT_LAYER,
                'api'     => [
                    'key'   => 'service/pageshot/ssl/key',
                    'value' => $this->config->getAppValue('service/pageshot/ssl/key')
                ]
            ],
            [
                'id'      => HelperService::PAGESHOT_SCREEN_SHOT_MACHINE,
                'label'   => $this->localisation->t('screenshotmachine.com'),
                'current' => $current === HelperService::PAGESHOT_SCREEN_SHOT_MACHINE,
                'api'     => [
                    'key'   => 'service/pageshot/ssm/key',
                    'value' => $this->config->getAppValue('service/pageshot/ssm/key')
                ]
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
    public function getSection() {
        return 'additional';
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the admin section. The forms are arranged in ascending order of the
     * priority values. It is required to return a value between 0 and 100.
     */
    public function getPriority() {
        return 70;
    }
}
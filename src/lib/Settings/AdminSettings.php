<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 18:12
 */

namespace OCA\Passwords\Settings;

use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FaviconService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\PageShotService;
use OCA\Passwords\Services\PasswordGenerationService;
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
     * @var IL10N
     */
    protected $localisation;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * AdminSettings constructor.
     *
     * @param IL10N                $localisationService
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     */
    public function __construct(IL10N $localisationService, ConfigurationService $config, FileCacheService $fileCacheService) {
        $this->localisation     = $localisationService;
        $this->config           = $config;
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
     * @since 9.1
     */
    public function getForm() {

        return new TemplateResponse('passwords', 'admin/index', [
            'wordsServices'    => $this->getWordsServices(),
            'faviconServices'  => $this->getFaviconServices(),
            'pageshotServices' => $this->getPageShotServices(),
            'caches' => $this->getFileCaches()
        ]);
    }

    /**
     * @return array
     */
    protected function getWordsServices(): array {
        $current = $this->config->getAppValue('service/words', PasswordGenerationService::SERVICE_SNAKES);

        return [
            [
                'id'      => PasswordGenerationService::SERVICE_LOCAL,
                'label'   => $this->localisation->t('Local'),
                'current' => $current === PasswordGenerationService::SERVICE_LOCAL
            ],
            [
                'id'      => PasswordGenerationService::SERVICE_SNAKES,
                'label'   => $this->localisation->t('watchout4snakes.com (recommended)'),
                'current' => $current === PasswordGenerationService::SERVICE_SNAKES
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getFaviconServices(): array {
        $current = $this->config->getAppValue('service/favicon', FaviconService::SERVICE_BETTER_IDEA);

        return [
            [
                'id'      => FaviconService::SERVICE_LOCAL,
                'label'   => $this->localisation->t('Local'),
                'current' => $current === FaviconService::SERVICE_LOCAL
            ],
            [
                'id'      => FaviconService::SERVICE_BETTER_IDEA,
                'label'   => $this->localisation->t('icons.better-idea.org (recommended)'),
                'current' => $current === FaviconService::SERVICE_BETTER_IDEA
            ],
            [
                'id'      => FaviconService::SERVICE_DUCK_DUCK_GO,
                'label'   => $this->localisation->t('duckduckgo.com'),
                'current' => $current === FaviconService::SERVICE_DUCK_DUCK_GO
            ],
            [
                'id'      => FaviconService::SERVICE_GOOGLE,
                'label'   => $this->localisation->t('google.com'),
                'current' => $current === FaviconService::SERVICE_GOOGLE
            ],
            [
                'id'      => FaviconService::SERVICE_DEFAULT,
                'label'   => $this->localisation->t('None'),
                'current' => $current === FaviconService::SERVICE_DEFAULT
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getPageShotServices(): array {
        $current = $this->config->getAppValue('service/pageshot', PageShotService::SERVICE_WKHTML);

        return [
            [
                'id'      => PageShotService::SERVICE_DEFAULT,
                'label'   => $this->localisation->t('None'),
                'current' => $current === PageShotService::SERVICE_DEFAULT,
                'api'     => null
            ],
            [
                'id'      => PageShotService::SERVICE_WKHTML,
                'label'   => $this->localisation->t('WKHTML (built-in)'),
                'current' => $current === PageShotService::SERVICE_WKHTML,
                'api'     => null
            ],
            [
                'id'      => PageShotService::SERVICE_SCREEN_SHOT_API,
                'label'   => $this->localisation->t('screenshotapi.io (recommended)'),
                'current' => $current === PageShotService::SERVICE_SCREEN_SHOT_API,
                'api'     => [
                    'key'   => 'service/pageshot/ssa/key',
                    'value' => $this->config->getAppValue('service/pageshot/ssa/key')
                ]
            ],
            [
                'id'      => PageShotService::SERVICE_SCREEN_SHOT_LAYER,
                'label'   => $this->localisation->t('screenshotlayer.com'),
                'current' => $current === PageShotService::SERVICE_SCREEN_SHOT_LAYER,
                'api'     => [
                    'key'   => 'service/pageshot/ssl/key',
                    'value' => $this->config->getAppValue('service/pageshot/ssl/key')
                ]
            ],
            [
                'id'      => PageShotService::SERVICE_SCREEN_SHOT_MACHINE,
                'label'   => $this->localisation->t('screenshotmachine.com'),
                'current' => $current === PageShotService::SERVICE_SCREEN_SHOT_MACHINE,
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

        $infos = [];
        foreach ($caches as $cache) {
            $infos[] = $this->fileCacheService->getCacheInfo($cache);
        }

        return $infos;
    }

    /**
     * @return string the section ID, e.g. 'sharing'
     * @since 9.1
     */
    public function getSection() {
        return 'additional';
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the admin section. The forms are arranged in ascending order of the
     * priority values. It is required to return a value between 0 and 100.
     *
     * E.g.: 70
     * @since 9.1
     */
    public function getPriority() {
        return 70;
    }
}
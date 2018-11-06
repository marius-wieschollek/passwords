<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Settings;

use Gmagick;
use Imagick;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Favicon\BestIconHelper;
use OCA\Passwords\Helper\Preview\ScreenShotApiHelper;
use OCA\Passwords\Helper\Preview\ScreenShotMachineHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\BackgroundJob;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

/**
 * Class AdminSettings
 *
 * @package OCA\Passwords\Settings
 */
class AdminSettings implements ISettings {

    const LINK_DOCUMENTATION = 'https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/Index';
    const LINK_HELP          = 'https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/Administrative-Settings';
    const LINK_REQUIREMENTS  = 'https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/System-Requirements';
    const LINK_ISSUES        = 'https://github.com/marius-wieschollek/passwords/issues';
    const LINK_FORUM         = 'https://help.nextcloud.com/c/apps/passwords';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * AdminSettings constructor.
     *
     * @param IURLGenerator        $urlGenerator
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     */
    public function __construct(
        IURLGenerator $urlGenerator,
        ConfigurationService $config,
        FileCacheService $fileCacheService
    ) {
        $this->config           = $config;
        $this->fileCacheService = $fileCacheService;
        $this->urlGenerator     = $urlGenerator;
    }

    /**
     * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
     */
    public function getForm(): TemplateResponse {
        return new TemplateResponse('passwords', 'admin/index', [
            'saveSettingsUrl'  => $this->urlGenerator->linkToRouteAbsolute('passwords.admin_settings.set'),
            'clearCacheUrl'    => $this->urlGenerator->linkToRouteAbsolute('passwords.admin_settings.cache'),
            'imageServices'    => $this->getImageServices(),
            'wordsServices'    => $this->getWordsServices(),
            'faviconServices'  => $this->getFaviconServices(),
            'previewServices'  => $this->getWebsitePreviewServices(),
            'securityServices' => $this->getSecurityServices(),
            'purgeTimeout'     => $this->getPurgeTimeout(),
            'backupInterval'   => $this->getBackupInterval(),
            'backupFiles'      => $this->config->getAppValue('backup/files/maximum', 14),
            'mailSecurity'     => $this->config->getAppValue('settings/mail/security', true),
            'mailSharing'      => $this->config->getAppValue('settings/mail/shares', false),
            'debugHTTPS'       => $this->config->getAppValue('debug/https', false),
            'legacyApiEnabled' => $this->config->getAppValue('legacy_api_enabled', true),
            'legacyLastUsed'   => $this->config->getAppValue('legacy_last_used', null),
            'nightlyUpdates'   => $this->config->getAppValue('nightly_updates', false),
            'caches'           => $this->getFileCaches(),
            'support'          => $this->getPlatformSupport(),
            'links'            => [
                'documentation' => self::LINK_DOCUMENTATION,
                'requirements'  => self::LINK_REQUIREMENTS,
                'issues'        => self::LINK_ISSUES,
                'forum'         => self::LINK_FORUM,
                'help'          => self::LINK_HELP
            ]
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
                'label'   => 'Have I been pwned? (recommended)',
                'current' => $current === HelperService::SECURITY_HIBP
            ],
            [
                'id'      => HelperService::SECURITY_BIG_LOCAL,
                'label'   => '10 Million Passwords (Local)',
                'current' => $current === HelperService::SECURITY_BIG_LOCAL
            ],
            [
                'id'      => HelperService::SECURITY_SMALL_LOCAL,
                'label'   => '1 Million Passwords (Local)',
                'current' => $current === HelperService::SECURITY_SMALL_LOCAL
            ],
            [
                'id'      => HelperService::SECURITY_BIGDB_HIBP,
                'label'   => '10Mio Passwords & Hibp?',
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
                'label'   => 'Local dictionary',
                'current' => $current === HelperService::WORDS_LOCAL
            ],
            [
                'id'      => HelperService::WORDS_SNAKES,
                'label'   => 'watchout4snakes.com (recommended)',
                'current' => $current === HelperService::WORDS_SNAKES
            ],
            [
                'id'      => HelperService::WORDS_RANDOM,
                'label'   => 'Random Characters',
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
                'label'   => 'Imagick/GMagick (recommended)',
                'current' => $current === HelperService::IMAGES_IMAGICK
            ],
            [
                'id'      => HelperService::IMAGES_GDLIB,
                'label'   => 'PHP GDLib',
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
                'label'   => 'Local analyzer',
                'current' => $current === HelperService::FAVICON_LOCAL,
                'api'     => null
            ],
            [
                'id'      => HelperService::FAVICON_BESTICON,
                'label'   => 'Besticon (recommended)',
                'current' => $current === HelperService::FAVICON_BESTICON,
                'api'     => [
                    'key'   => BestIconHelper::BESTICON_CONFIG_KEY,
                    'value' => $this->config->getAppValue(BestIconHelper::BESTICON_CONFIG_KEY, BestIconHelper::BESTICON_DEFAULT_URL)
                ]
            ],
            [
                'id'      => HelperService::FAVICON_FAVICON_GRABBER,
                'label'   => 'favicongrabber.com',
                'current' => $current === HelperService::FAVICON_FAVICON_GRABBER,
                'api'     => null
            ],
            [
                'id'      => HelperService::FAVICON_DUCK_DUCK_GO,
                'label'   => 'DuckDuckGo',
                'current' => $current === HelperService::FAVICON_DUCK_DUCK_GO,
                'api'     => null
            ],
            [
                'id'      => HelperService::FAVICON_GOOGLE,
                'label'   => 'Google',
                'current' => $current === HelperService::FAVICON_GOOGLE,
                'api'     => null
            ],
            [
                'id'      => HelperService::FAVICON_DEFAULT,
                'label'   => 'None',
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
                'label'   => 'WKHTML (Local)',
                'current' => $current === HelperService::PREVIEW_WKHTML,
                'api'     => null
            ],
            [
                'id'      => HelperService::PREVIEW_PAGERES,
                'label'   => 'Pageres/PhantomJS (Local)',
                'current' => $current === HelperService::PREVIEW_PAGERES,
                'api'     => null
            ],
            [
                'id'      => HelperService::PREVIEW_SCREEN_SHOT_API,
                'label'   => 'screenshotapi.io',
                'current' => $current === HelperService::PREVIEW_SCREEN_SHOT_API,
                'api'     => [
                    'key'   => ScreenShotApiHelper::SSA_API_CONFIG_KEY,
                    'value' => $this->config->getAppValue(ScreenShotApiHelper::SSA_API_CONFIG_KEY)
                ]
            ],
            [
                'id'      => HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                'label'   => 'screenshotmachine.com',
                'current' => $current === HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                'api'     => [
                    'key'   => ScreenShotMachineHelper::SSM_API_CONFIG_KEY,
                    'value' => $this->config->getAppValue(ScreenShotMachineHelper::SSM_API_CONFIG_KEY)
                ]
            ],
            [
                'id'      => HelperService::PREVIEW_DEFAULT,
                'label'   => 'None',
                'current' => $current === HelperService::PREVIEW_DEFAULT,
                'api'     => null
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getPurgeTimeout(): array {
        return [
            'current' => $this->config->getAppValue('entity/purge/timeout', -1),
            'options' => [
                -1       => 'Never',
                0        => 'Immediately',
                7200     => 'After two hours',
                86400    => 'After one day',
                1209600  => 'After two weeks',
                2592000  => 'After one month',
                31536000 => 'After one year'
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getBackupInterval(): array {
        return [
            'current' => $this->config->getAppValue('backup/interval', 86400),
            'options' => [
                3600    => 'Every hour',
                21600   => 'Every six hours',
                86400   => 'Every day',
                172800  => 'Every two days',
                604800  => 'Every week',
                1209600 => 'Every two weeks'
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
                $info[ $cache ]              = $this->fileCacheService->getCacheInfo($cache);
                $info[ $cache ]['clearable'] = true;
            } catch(\Exception $e) {
            }
        }

        if(
            $this->config->getAppValue('service/favicon') === HelperService::FAVICON_BESTICON &&
            $this->config->getAppValue(BestIconHelper::BESTICON_CONFIG_KEY, BestIconHelper::BESTICON_DEFAULT_URL) === BestIconHelper::BESTICON_DEFAULT_URL
        ) {
            $info[ $this->fileCacheService::FAVICON_CACHE ]['clearable'] = false;
        }

        return $info;
    }

    /**
     * @return array
     */
    protected function getPlatformSupport(): array {
        $ncVersion = intval(explode('.', \OC::$server->getConfig()->getSystemValue('version'), 2)[0]);
        $cronType  = \OC::$server->getConfig()->getAppValue('core', 'backgroundjobs_mode', 'ajax');

        if(BackgroundJob::getExecutionType() !== '') $cronType = BackgroundJob::getExecutionType();

        return [
            'cron'   => $cronType === 'ajax',
            'https'  => \OC::$server->getRequest()->getHttpProtocol() === 'https',
            'wkhtml' => $this->config->getAppValue('service/preview') == HelperService::PREVIEW_WKHTML,
            'php'    => [
                'warn'    => PHP_VERSION_ID < 70200,
                'error'   => PHP_VERSION_ID < 70100,
                'version' => PHP_VERSION
            ],
            'server' => [
                'warn'    => $ncVersion < 14,
                'error'   => $ncVersion < 12,
                'version' => $ncVersion
            ],
            'eol'    => '2019.1.0'
        ];
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

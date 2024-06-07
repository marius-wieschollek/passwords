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

namespace OCA\Passwords\Settings;

use Exception;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\AppInfo\SystemRequirements;
use OCA\Passwords\Encryption\Object\SseV3KeyProviderInterface;
use OCA\Passwords\Provider\Favicon\BestIconProvider;
use OCA\Passwords\Provider\Preview\BrowshotPreviewProvider;
use OCA\Passwords\Provider\Preview\ScreeenlyProvider;
use OCA\Passwords\Provider\Preview\ScreenShotLayerProvider;
use OCA\Passwords\Provider\Preview\ScreenShotMachineProvider;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

/**
 * Class AdminSettings
 *
 * @package OCA\Passwords\Settings
 */
class AdminSettings implements ISettings {

    const LINK_DOCUMENTATION = 'https://git.mdns.eu/nextcloud/passwords/-/wikis/Administrators/Index';
    const LINK_HELP          = 'https://git.mdns.eu/nextcloud/passwords/-/wikis/Administrators/App-Settings';
    const LINK_REQUIREMENTS  = 'https://git.mdns.eu/nextcloud/passwords/-/wikis/Administrators/System-Requirements';
    const LINK_ISSUES        = 'https://github.com/marius-wieschollek/passwords/issues';
    const LINK_FORUM         = 'https://help.nextcloud.com/c/apps/passwords';

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var IRequest
     */
    protected IRequest $request;

    /**
     * @var IURLGenerator
     */
    protected IURLGenerator $urlGenerator;

    /**
     * @var HelperService
     */
    protected HelperService $helperService;

    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCacheService;

    /**
     * AdminSettings constructor.
     *
     * @param IRequest             $request
     * @param IURLGenerator        $urlGenerator
     * @param ConfigurationService $config
     * @param HelperService        $helperService
     * @param FileCacheService     $fileCacheService
     */
    public function __construct(
        IRequest $request,
        IURLGenerator $urlGenerator,
        ConfigurationService $config,
        HelperService $helperService,
        FileCacheService $fileCacheService
    ) {
        $this->request          = $request;
        $this->config           = $config;
        $this->urlGenerator     = $urlGenerator;
        $this->helperService    = $helperService;
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
     */
    public function getForm(): TemplateResponse {
        $variables = [
            'imageServices'    => $this->getImageServices(),
            'wordsServices'    => $this->getWordsServices(),
            'faviconServices'  => $this->getFaviconServices(),
            'previewServices'  => $this->getWebsitePreviewServices(),
            'securityServices' => $this->getSecurityServices(),
            'purgeTimeout'     => $this->getPurgeTimeout(),
            'backupInterval'   => $this->getBackupInterval(),
            'securityHash'     => $this->getSecurityHash(),
            'backupFiles'      => intval($this->config->getAppValue('backup/files/maximum', 14)),
            'backupRestore'    => $this->config->getAppValue('backup/update/autorestore', true),
            'serverSurvey'     => intval($this->config->getAppValue('survey/server/mode', -1)),
            'mailSecurity'     => $this->config->getAppValue('settings/mail/security', true),
            'mailSharing'      => $this->config->getAppValue('settings/mail/shares', false),
            'nightlyUpdates'   => $this->config->getAppValue('nightly/enabled', false),
            'encryptionSSEv3'  => $this->config->getAppValue('encryption/SSEv3/enabled', false),
            'hasSSEv3'         => \OC::$server->has(SseV3KeyProviderInterface::class),
            'caches'           => $this->getFileCaches(),
            'support'          => $this->getPlatformSupport(),
            'links'            => [
                'documentation' => self::LINK_DOCUMENTATION,
                'requirements'  => self::LINK_REQUIREMENTS,
                'issues'        => self::LINK_ISSUES,
                'forum'         => self::LINK_FORUM,
                'help'          => self::LINK_HELP
            ]
        ];

        if($this->config->hasAppValue('dev/app/hash')) {
            $variables['hash'] = $this->config->getAppValue('dev/app/hash');
        }

        return new TemplateResponse('passwords', 'admin/index', $variables);
    }

    /**
     * @return array
     * @deprecated
     */
    protected function getSecurityServices(): array {
        $current = $this->config->getAppValue('service/security', HelperService::SECURITY_HIBP);

        return [
            [
                'id'      => HelperService::SECURITY_HIBP,
                'label'   => 'Have I been pwned? (recommended)',
                'current' => $current === HelperService::SECURITY_HIBP,
                'enabled' => $this->helperService->getSecurityHelper(HelperService::SECURITY_HIBP)->isAvailable()
            ],
            [
                'id'      => HelperService::SECURITY_BIG_LOCAL,
                'label'   => 'Big local database (25M passwords)',
                'current' => $current === HelperService::SECURITY_BIG_LOCAL,
                'enabled' => $this->helperService->getSecurityHelper(HelperService::SECURITY_BIG_LOCAL)->isAvailable()
            ],
            [
                'id'      => HelperService::SECURITY_SMALL_LOCAL,
                'label'   => 'Small local database (5M passwords)',
                'current' => $current === HelperService::SECURITY_SMALL_LOCAL,
                'enabled' => $this->helperService->getSecurityHelper(HelperService::SECURITY_SMALL_LOCAL)->isAvailable()
            ],
            [
                'id'      => HelperService::SECURITY_BIGDB_HIBP,
                'label'   => 'Big local database & Hibp?',
                'current' => $current === HelperService::SECURITY_BIGDB_HIBP,
                'enabled' => $this->helperService->getSecurityHelper(HelperService::SECURITY_BIGDB_HIBP)->isAvailable()
            ]
        ];
    }

    /**
     * @return array[]
     */
    protected function getWordsServices(): array {
        $current = $this->config->getAppValue('service/words', HelperService::WORDS_AUTO);

        return [
            [
                'id'      => HelperService::WORDS_AUTO,
                'label'   => 'Select automatically (recommended)',
                'current' => $current === HelperService::WORDS_AUTO,
                'enabled' => $this->helperService->getWordsHelper(HelperService::WORDS_AUTO)->isAvailable()
            ],
            [
                'id'      => HelperService::WORDS_LEIPZIG,
                'label'   => 'Leipzig Corpora Collection',
                'current' => $current === HelperService::WORDS_LEIPZIG,
                'enabled' => $this->helperService->getWordsHelper(HelperService::WORDS_LEIPZIG)->isAvailable()
            ],
            [
                'id'      => HelperService::WORDS_LOCAL,
                'label'   => 'Local dictionary',
                'current' => $current === HelperService::WORDS_LOCAL,
                'enabled' => $this->helperService->getWordsHelper(HelperService::WORDS_LOCAL)->isAvailable()
            ],
            [
                'id'      => HelperService::WORDS_SNAKES,
                'label'   => 'watchout4snakes.com',
                'current' => $current === HelperService::WORDS_SNAKES,
                'enabled' => $this->helperService->getWordsHelper(HelperService::WORDS_SNAKES)->isAvailable()
            ],
            [
                'id'      => HelperService::WORDS_RANDOM,
                'label'   => 'Random Characters',
                'current' => $current === HelperService::WORDS_RANDOM,
                'enabled' => $this->helperService->getWordsHelper(HelperService::WORDS_RANDOM)->isAvailable()
            ]
        ];
    }

    /**
     * @return array
     * @deprecated
     */
    protected function getImageServices(): array {
        $current = $this->config->getAppValue('service/images', HelperService::IMAGES_AUTO);

        return [
            [
                'id'      => HelperService::IMAGES_AUTO,
                'label'   => 'Select automatically (recommended)',
                'current' => $current === HelperService::IMAGES_AUTO,
                'enabled' => true,
            ],
            [
                'id'      => HelperService::IMAGES_IMAGICK,
                'label'   => 'Imagick/GMagick',
                'current' => $current === HelperService::IMAGES_IMAGICK,
                'enabled' => $this->helperService->getImageHelper(HelperService::IMAGES_IMAGICK)->isAvailable(),
            ],
            [
                'id'      => HelperService::IMAGES_IMAGINARY,
                'label'   => 'Imaginary',
                'current' => $current === HelperService::IMAGES_IMAGINARY,
                'enabled' => $this->helperService->getImageHelper(HelperService::IMAGES_IMAGINARY)->isAvailable(),
            ],
            [
                'id'      => HelperService::IMAGES_GDLIB,
                'label'   => 'PHP GDLib',
                'current' => $current === HelperService::IMAGES_GDLIB,
                'enabled' => true
            ]
        ];
    }

    /**
     * @return array
     * @deprecated
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
                    'key'   => 'service.favicon.api',
                    'value' => $this->config->getAppValue(BestIconProvider::BESTICON_CONFIG_KEY, '')
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
     * @deprecated
     */
    protected function getWebsitePreviewServices(): array {
        $current = $this->config->getAppValue('service/preview', HelperService::PREVIEW_DEFAULT);

        return [
            [
                'id'      => HelperService::PREVIEW_PAGERES,
                'label'   => 'Pageres CLI (Local)',
                'current' => $current === HelperService::PREVIEW_PAGERES,
                'api'     => null
            ],
            [
                'id'      => HelperService::PREVIEW_SCREEN_SHOT_LAYER,
                'label'   => 'screenshotlayer',
                'current' => $current === HelperService::PREVIEW_SCREEN_SHOT_LAYER,
                'api'     => [
                    'key'   => 'service.preview.api',
                    'value' => $this->config->getAppValue(ScreenShotLayerProvider::SSL_API_CONFIG_KEY)
                ]
            ],
            [
                'id'      => HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                'label'   => 'screenshotmachine.com',
                'current' => $current === HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                'api'     => [
                    'key'   => 'service.preview.api',
                    'value' => $this->config->getAppValue(ScreenShotMachineProvider::SSM_API_CONFIG_KEY)
                ]
            ],
            [
                'id'      => HelperService::PREVIEW_BROW_SHOT,
                'label'   => 'Browshot',
                'current' => $current === HelperService::PREVIEW_BROW_SHOT,
                'api'     => [
                    'key'   => 'service.preview.api',
                    'value' => $this->config->getAppValue(BrowshotPreviewProvider::BWS_API_CONFIG_KEY)
                ]
            ],
            [
                'id'      => HelperService::PREVIEW_SCREEENLY,
                'label'   => 'screeenly',
                'current' => $current === HelperService::PREVIEW_SCREEENLY,
                'api'     => [
                    'key'   => 'service.preview.api',
                    'value' => $this->config->getAppValue(ScreeenlyProvider::SCREEENLY_API_CONFIG_KEY)
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
     * @deprecated
     */
    protected function getPurgeTimeout(): array {
        return [
            'current' => (int) $this->config->getAppValue('entity/purge/timeout', -1),
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
     * @deprecated
     */
    protected function getBackupInterval(): array {
        return [
            'current' => intval($this->config->getAppValue('backup/interval', 86400)),
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
     * @deprecated
     */
    protected function getSecurityHash(): array {
        return [
            'current' => intval($this->config->getAppValue('settings/password/security/hash', 40)),
            'options' => [
                0  => 'Don\'t store hashes',
                20 => 'Store 50%% of the hash',
                30 => 'Store 75%% of the hash',
                40 => 'Store the full hash',
            ]
        ];
    }

    /**
     * @return array
     * @deprecated
     */
    protected function getFileCaches(): array {
        $caches = $this->fileCacheService->listCaches();

        $info = [];
        foreach($caches as $cache) {
            try {
                $info[ $cache ]              = $this->fileCacheService->getCacheInfo($cache);
                $info[ $cache ]['clearable'] = true;
            } catch(Exception $e) {
            }
        }

        return $info;
    }

    /**
     * @return array
     */
    protected function getPlatformSupport(): array {
        $ncVersion     = \OC_Util::getVersion()[0];
        $cronType      = $this->config->getAppValue('backgroundjobs_mode', 'ajax', 'core');
        $cronPhpId     = $this->config->getAppValue('cron/php/version/id', PHP_VERSION_ID);
        $cronPhpString = $this->config->getAppValue('cron/php/version/string', phpversion());
        $this->config->setAppValue('web/php/version/id', PHP_VERSION_ID);
        $this->config->setAppValue('web/php/version/string', phpversion());

        return [
            'cron'    => $cronType,
            'https'   => $this->request->getHttpProtocol() === 'https',
            'lsr'     => SystemRequirements::APP_LSR,
            'php'     => [
                'error'   => PHP_VERSION_ID < SystemRequirements::PHP_MINIMUM_ID,
                'version' => phpversion()
            ],
            'cronPhp' => [
                'isDifferent' => PHP_VERSION_ID - $cronPhpId > 99 || $cronPhpId - PHP_VERSION_ID > 99,
                'error'       => $cronPhpId < SystemRequirements::PHP_MINIMUM_ID,
                'cronVersion' => $cronPhpString
            ],
            'server'  => [
                'error'   => $ncVersion < SystemRequirements::NC_MINIMUM_ID,
                'version' => $ncVersion
            ]
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

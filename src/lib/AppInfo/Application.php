<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 17:01
 */

namespace OCA\Passwords\AppInfo;

use OCA\Passwords\Controller\AccessController;
use OCA\Passwords\Controller\AdminSettingsController;
use OCA\Passwords\Controller\Api\FolderApiController;
use OCA\Passwords\Controller\Api\PasswordApiController;
use OCA\Passwords\Controller\Api\ServiceApiController;
use OCA\Passwords\Controller\Api\TagApiController;
use OCA\Passwords\Controller\PageController;
use OCA\Passwords\Cron\CheckPasswordsJob;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\PasswordTagRelationMapper;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Helper\ApiObjects\FolderObjectHelper;
use OCA\Passwords\Helper\ApiObjects\PasswordObjectHelper;
use OCA\Passwords\Helper\ApiObjects\TagObjectHelper;
use OCA\Passwords\Helper\Favicon\BetterIdeaHelper;
use OCA\Passwords\Helper\Favicon\DefaultFaviconHelper;
use OCA\Passwords\Helper\Favicon\DuckDuckGoHelper;
use OCA\Passwords\Helper\Favicon\GoogleFaviconHelper;
use OCA\Passwords\Helper\Favicon\LocalFaviconHelper;
use OCA\Passwords\Helper\Image\GdHelper;
use OCA\Passwords\Helper\Image\ImagickHelper;
use OCA\Passwords\Helper\PageShot\DefaultPageShotHelper;
use OCA\Passwords\Helper\PageShot\ScreenShotApiHelper;
use OCA\Passwords\Helper\PageShot\ScreenShotLayerHelper;
use OCA\Passwords\Helper\PageShot\ScreenShotMachineHelper;
use OCA\Passwords\Helper\PageShot\WkhtmlImageHelper;
use OCA\Passwords\Helper\SecurityCheck\BigLocalDbSecurityCheckHelper;
use OCA\Passwords\Helper\SecurityCheck\HaveIBeenPwnedHelper;
use OCA\Passwords\Helper\SecurityCheck\SmallLocalDbSecurityCheckHelper;
use OCA\Passwords\Helper\Words\LocalWordsHelper;
use OCA\Passwords\Helper\Words\RandomCharactersHelper;
use OCA\Passwords\Helper\Words\SnakesWordsHelper;
use OCA\Passwords\Hooks\FolderHook;
use OCA\Passwords\Hooks\FolderRevisionHook;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Hooks\PasswordHook;
use OCA\Passwords\Hooks\PasswordRevisionHook;
use OCA\Passwords\Hooks\TagHook;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\FaviconService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCA\Passwords\Services\PageShotService;
use OCA\Passwords\Services\ValidationService;
use OCA\Passwords\Services\WordsService;
use OCA\Passwords\Settings\AdminSettings;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\Files\IAppData;

/**
 * Class Application
 *
 * @package OCA\Passwords\AppInfo
 */
class Application extends App {

    const APP_NAME = 'passwords';

    /**
     * Application constructor.
     *
     * @param array $urlParams
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_NAME, $urlParams);

        $this->registerPersonalSettings();
        $this->registerDiClasses();
    }

    /**
     *
     */
    protected function registerPersonalSettings(): void {
        \OCP\App::registerPersonal(self::APP_NAME, 'personal/index');
    }

    /**
     * @throws \OCP\AppFramework\QueryException
     */
    protected function registerDiClasses(): void {
        $container = $this->getContainer();

        /**
         * Controllers
         */
        $this->registerController();

        /**
         * Mappers
         */
        $this->registerMapper();

        /**
         * Hooks
         */
        $this->registerHooks();

        /**
         * Services
         */
        $this->registerServices();

        /**
         * Helper
         */
        $this->registerApiHelper();
        $this->registerImageHelper();
        $this->registerPageShotHelper();
        $this->registerFaviconHelper();
        $this->registerWordsHelper();
        $this->registerSecurityCheckHelper();

        /**
         * Admin Settings
         */
        $container->registerService('AdminSettings', function (IAppContainer $c) {
            return new AdminSettings(
                $c->query('LocalisationService'),
                $c->query('ConfigurationService'),
                $this->getFileCacheService()
            );
        });

        /**
         * Cron Jobs
         */
        $container->registerService('CheckPasswordsJob', function (IAppContainer $c) {
            return new CheckPasswordsJob(
                $c->query('HelperService'),
                $c->query('PasswordRevisionMapper')
            );
        });

        /**
         * Alias
         */
        $container->registerAlias('AppData', IAppData::class);
        $container->registerAlias('EncryptionService', EncryptionService::class);

        /**
         * Register Hooks
         */
        $this->registerInternalHooks();
    }

    /**
     * @return FileCacheService
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getFileCacheService(): FileCacheService {
        return clone $this->getContainer()->query('FileCacheService');
    }

    /**
     * @return string
     */
    protected function getUserId(): string {
        $user = $this->getContainer()->getServer()->getUserSession()->getUser();

        return $user === null ? '':$user->getUID();
    }

    /**
     *
     */
    protected function registerController(): void {
        $container = $this->getContainer();

        $container->registerService('PageController', function (IAppContainer $c) {
            return new PageController(
                self::APP_NAME,
                $c->query('Request')
            );
        });

        $container->registerService('AccessController', function (IAppContainer $c) {
            return new AccessController(
                self::APP_NAME,
                $c->query('Request'),
                $c->getServer()->getURLGenerator()
            );
        });

        $container->registerService('PasswordApiController', function (IAppContainer $c) {
            return new PasswordApiController(
                self::APP_NAME,
                $c->query('Request'),
                $c->query('TagService'),
                $c->query('PasswordService'),
                $c->query('TagRevisionService'),
                $c->query('PasswordRevisionService'),
                $c->query('PasswordObjectHelper'),
                $c->query('PasswordTagRelationService')
            );
        });

        $container->registerService('FolderApiController', function (IAppContainer $c) {
            return new FolderApiController(
                self::APP_NAME,
                $c->query('Request'),
                $c->query('FolderService'),
                $c->query('FolderRevisionService'),
                $c->query('FolderObjectHelper')
            );
        });

        $container->registerService('TagApiController', function (IAppContainer $c) {
            return new TagApiController(
                self::APP_NAME,
                $c->query('Request'),
                $c->query('TagService'),
                $c->query('TagRevisionService'),
                $c->query('TagObjectHelper')
            );
        });

        $container->registerService('ServiceApiController', function (IAppContainer $c) {
            return new ServiceApiController(
                self::APP_NAME,
                $c->query('Request'),
                $c->query('FaviconService'),
                $c->query('PageShotService'),
                $c->query('WordsService')
            );
        });

        $container->registerService('AdminSettingsController', function (IAppContainer $c) {
            return new AdminSettingsController(
                self::APP_NAME,
                $c->query('Request'),
                $c->getServer()->getConfig(),
                $this->getFileCacheService()
            );
        });
    }

    /**
     *
     */
    protected function registerMapper(): void {
        $container = $this->getContainer();

        $container->registerService('PasswordMapper', function (IAppContainer $c) {
            return new PasswordMapper(
                $c->getServer()->getDatabaseConnection(),
                $this->getUserId()
            );
        });

        $container->registerService('PasswordRevisionMapper', function (IAppContainer $c) {
            return new PasswordRevisionMapper(
                $c->getServer()->getDatabaseConnection(),
                $this->getUserId()
            );
        });

        $container->registerService('FolderMapper', function (IAppContainer $c) {
            return new FolderMapper(
                $c->getServer()->getDatabaseConnection(),
                $this->getUserId()
            );
        });
        $container->registerService('FolderRevisionMapper', function (IAppContainer $c) {
            return new FolderRevisionMapper(
                $c->getServer()->getDatabaseConnection(),
                $this->getUserId()
            );
        });

        $container->registerService('TagMapper', function (IAppContainer $c) {
            return new TagMapper(
                $c->getServer()->getDatabaseConnection(),
                $this->getUserId()
            );
        });

        $container->registerService('TagRevisionMapper', function (IAppContainer $c) {
            return new TagRevisionMapper(
                $c->getServer()->getDatabaseConnection(),
                $this->getUserId()
            );
        });

        $container->registerService('PasswordTagRelationMapper', function (IAppContainer $c) {
            return new PasswordTagRelationMapper(
                $c->getServer()->getDatabaseConnection(),
                $this->getUserId()
            );
        });
    }

    /**
     *
     */
    protected function registerHooks(): void {
        $container = $this->getContainer();

        $container->registerService('HookManager', function () {
            return new HookManager();
        });
        $container->registerService('FolderHook', function (IAppContainer $c) {
            return new FolderHook(
                $c->query('FolderService'),
                $c->query('FolderRevisionService'),
                $c->query('PasswordService')
            );
        });
        $container->registerService('FolderRevisionHook', function (IAppContainer $c) {
            return new FolderRevisionHook(
                $c->query('FolderService'),
                $c->query('FolderRevisionService'),
                $c->query('PasswordService'),
                $c->query('PasswordRevisionService')
            );
        });
        $container->registerService('PasswordHook', function (IAppContainer $c) {
            return new PasswordHook(
                $c->query('TagRevisionService'),
                $c->query('PasswordRevisionService'),
                $c->query('PasswordTagRelationService')
            );
        });
        $container->registerService('TagHook', function (IAppContainer $c) {
            return new TagHook(
                $c->query('TagRevisionService'),
                $c->query('PasswordRevisionService'),
                $c->query('PasswordTagRelationService')
            );
        });
    }

    /**
     *
     */
    protected function registerServices(): void {
        $container = $this->getContainer();

        if($this->getContainer()->getServer()->getUserSession()->getUser()) {
            $container->registerService('FolderService', function (IAppContainer $c) {
                return new FolderService(
                    $c->getServer()->getUserSession()->getUser(),
                    $c->query('HookManager'),
                    $c->query('FolderMapper')
                );
            });

            $container->registerService('FolderRevisionService', function (IAppContainer $c) {
                return new FolderRevisionService(
                    $c->getServer()->getUserSession()->getUser(),
                    $c->query('HookManager'),
                    $c->query('FolderRevisionMapper'),
                    $c->query('ValidationService'),
                    $c->query('EncryptionService')
                );
            });

            $container->registerService('PasswordService', function (IAppContainer $c) {
                return new PasswordService(
                    $c->getServer()->getUserSession()->getUser(),
                    $c->query('HookManager'),
                    $c->query('PasswordMapper')
                );
            });

            $container->registerService('PasswordRevisionService', function (IAppContainer $c) {
                return new PasswordRevisionService(
                    $c->getServer()->getUserSession()->getUser(),
                    $c->query('HookManager'),
                    $c->query('PasswordRevisionMapper'),
                    $c->query('ValidationService'),
                    $c->query('EncryptionService')
                );
            });

            $container->registerService('TagService', function (IAppContainer $c) {
                return new TagService(
                    $c->getServer()->getUserSession()->getUser(),
                    $c->query('HookManager'),
                    $c->query('TagMapper')
                );
            });

            $container->registerService('TagRevisionService', function (IAppContainer $c) {
                return new TagRevisionService(
                    $c->getServer()->getUserSession()->getUser(),
                    $c->query('HookManager'),
                    $c->query('TagRevisionMapper'),
                    $c->query('ValidationService'),
                    $c->query('EncryptionService')
                );
            });

            $container->registerService('PasswordTagRelationService', function (IAppContainer $c) {
                return new PasswordTagRelationService(
                    $c->getServer()->getUserSession()->getUser(),
                    $c->query('HookManager'),
                    $c->query('PasswordTagRelationMapper')
                );
            });
        }

        $container->registerService('FileCacheService', function (IAppContainer $c) {
            return new FileCacheService(
                $c->query('AppData')
            );
        });

        $container->registerService('FaviconService', function (IAppContainer $c) {
            return new FaviconService(
                $c->query('HelperService'),
                $this->getFileCacheService(),
                $c->query('ValidationService'),
                $c->getServer()->getLogger()
            );
        });

        $container->registerService('PageShotService', function (IAppContainer $c) {
            return new PageShotService(
                $c->query('HelperService'),
                $this->getFileCacheService(),
                $c->query('ValidationService'),
                $c->getServer()->getLogger()
            );
        });

        $container->registerService('WordsService', function (IAppContainer $c) {
            return new WordsService(
                $c->query('HelperService'),
                $c->getServer()->getLogger()
            );
        });

        $container->registerService('HelperService', function (IAppContainer $c) {
            return new HelperService(
                $c->query('ConfigurationService'),
                $c->query('FileCacheService'),
                $c
            );
        });

        $container->registerService('ConfigurationService', function (IAppContainer $c) {
            return new ConfigurationService(
                $this->getUserId(),
                $c->getServer()->getConfig()
            );
        });

        $container->registerService('LocalisationService', function (IAppContainer $c) {
            return $c->query('L10NFactory')->get(self::APP_NAME);
        });

        $container->registerService('ValidationService', function (IAppContainer $c) {
            return new ValidationService(
                $c->query('HelperService')->getSecurityHelper()
            );
        });
    }

    /**
     *
     */
    protected function registerApiHelper(): void {
        $container = $this->getContainer();

        $container->registerService('PasswordObjectHelper', function (IAppContainer $c) {
            return new PasswordObjectHelper(
                $c,
                $c->query('TagService'),
                $c->query('FolderService'),
                $c->query('PasswordRevisionService')
            );
        });
        $container->registerService('FolderObjectHelper', function (IAppContainer $c) {
            return new FolderObjectHelper(
                $c,
                $c->query('FolderService'),
                $c->query('PasswordService'),
                $c->query('FolderRevisionService')
            );
        });
        $container->registerService('TagObjectHelper', function (IAppContainer $c) {
            return new TagObjectHelper(
                $c,
                $c->query('TagService'),
                $c->query('PasswordService'),
                $c->query('TagRevisionService')
            );
        });
    }

    /**
     *
     */
    protected function registerImageHelper(): void {
        $container = $this->getContainer();

        $container->registerService('ImagickHelper', function (IAppContainer $c) {
            return new ImagickHelper(
                $c->query('ConfigurationService')
            );
        });

        $container->registerService('GdHelper', function (IAppContainer $c) {
            return new GdHelper(
                $c->query('ConfigurationService')
            );
        });
    }

    /**
     *
     */
    protected function registerPageShotHelper(): void {
        $container = $this->getContainer();

        $container->registerService('WkhtmlImageHelper', function (IAppContainer $c) {
            return new WkhtmlImageHelper(
                $this->getFileCacheService(),
                $c->query('ConfigurationService')
            );
        });

        $container->registerService('ScreenShotApiHelper', function (IAppContainer $c) {
            return new ScreenShotApiHelper(
                $this->getFileCacheService(),
                $c->query('ConfigurationService')
            );
        });

        $container->registerService('ScreenShotLayerHelper', function (IAppContainer $c) {
            return new ScreenShotLayerHelper(
                $this->getFileCacheService(),
                $c->query('ConfigurationService')
            );
        });

        $container->registerService('ScreenShotMachineHelper', function (IAppContainer $c) {
            return new ScreenShotMachineHelper(
                $this->getFileCacheService(),
                $c->query('ConfigurationService')
            );
        });

        $container->registerService('DefaultPageShotHelper', function (IAppContainer $c) {
            return new DefaultPageShotHelper(
                $this->getFileCacheService(),
                $c->query('ConfigurationService')
            );
        });
    }

    /**
     *
     */
    protected function registerFaviconHelper(): void {
        $container = $this->getContainer();

        $container->registerService('BetterIdeaHelper', function (IAppContainer $c) {
            return new BetterIdeaHelper(
                $this->getFileCacheService(),
                $c->query('HelperService')->getImageHelper(),
                $c->query('OC_Defaults')
            );
        });

        $container->registerService('DuckDuckGoHelper', function (IAppContainer $c) {
            return new DuckDuckGoHelper(
                $this->getFileCacheService(),
                $c->query('HelperService')->getImageHelper(),
                $c->query('OC_Defaults')
            );
        });

        $container->registerService('GoogleFaviconHelper', function (IAppContainer $c) {
            return new GoogleFaviconHelper(
                $this->getFileCacheService(),
                $c->query('HelperService')->getImageHelper(),
                $c->query('OC_Defaults')
            );
        });

        $container->registerService('LocalFaviconHelper', function (IAppContainer $c) {
            return new LocalFaviconHelper(
                $this->getFileCacheService(),
                $c->query('HelperService')->getImageHelper(),
                $c->query('OC_Defaults')
            );
        });

        $container->registerService('DefaultFaviconHelper', function (IAppContainer $c) {
            return new DefaultFaviconHelper(
                $this->getFileCacheService(),
                $c->query('HelperService')->getImageHelper(),
                $c->query('OC_Defaults')
            );
        });
    }

    /**
     *
     */
    protected function registerWordsHelper(): void {
        $container = $this->getContainer();

        $container->registerService('LocalWordsHelper', function (IAppContainer $c) {
            return new LocalWordsHelper(
                $c->query('L10NFactory')->get('core')->getLanguageCode()
            );
        });

        $container->registerService('SnakesWordsHelper', function () {
            return new SnakesWordsHelper();
        });

        $container->registerService('RandomCharactersHelper', function () {
            return new RandomCharactersHelper();
        });
    }

    /**
     *
     */
    protected function registerSecurityCheckHelper(): void {
        $container = $this->getContainer();

        $container->registerService('HaveIBeenPwnedHelper', function (IAppContainer $c) {
            return new HaveIBeenPwnedHelper(
                $this->getFileCacheService(),
                $c->query('ConfigurationService'),
                $c->getServer()->getLogger()
            );
        });

        $container->registerService('BigLocalDbSecurityCheckHelper', function (IAppContainer $c) {
            return new BigLocalDbSecurityCheckHelper(
                $this->getFileCacheService(),
                $c->query('ConfigurationService'),
                $c->getServer()->getLogger()
            );
        });

        $container->registerService('SmallLocalDbSecurityCheckHelper', function (IAppContainer $c) {
            return new SmallLocalDbSecurityCheckHelper(
                $this->getFileCacheService(),
                $c->query('ConfigurationService'),
                $c->getServer()->getLogger()
            );
        });
    }

    /**
     * @throws \OCP\AppFramework\QueryException
     */
    protected function registerInternalHooks(): void {
        $container = $this->getContainer();
        /** @var HookManager $hookManager */
        $hookManager = $container->query('HookManager');
        /** @var FolderHook $folderHook */
        $folderHook = $container->query('FolderHook');
        $hookManager->listen(Folder::class, 'postClone', [$folderHook, 'postClone']);
        $hookManager->listen(Folder::class, 'preDelete', [$folderHook, 'preDelete']);
        $hookManager->listen(Folder::class, 'postDelete', [$folderHook, 'postDelete']);
        /** @var FolderRevisionHook $folderHook */
        $folderRevisionHook = $container->query('FolderRevisionHook');
        $hookManager->listen(FolderRevision::class, 'postClone', [$folderRevisionHook, 'postClone']);
        /** @var PasswordHook $passwordHook */
        $passwordHook = $container->query('PasswordHook');
        $hookManager->listen(Password::class, 'postClone', [$passwordHook, 'postClone']);
        $hookManager->listen(Password::class, 'preDelete', [$passwordHook, 'preDelete']);
        $hookManager->listen(Password::class, 'postDelete', [$passwordHook, 'postDelete']);
        $hookManager->listen(Password::class, 'preSetRevision', [$passwordHook, 'preSetRevision']);
        /** @var TagHook $passwordHook */
        $tagHook = $container->query('TagHook');
        $hookManager->listen(Tag::class, 'postClone', [$tagHook, 'postClone']);
        $hookManager->listen(Tag::class, 'preDelete', [$tagHook, 'preDelete']);
        $hookManager->listen(Tag::class, 'postDelete', [$tagHook, 'postDelete']);
        $hookManager->listen(Tag::class, 'preSetRevision', [$tagHook, 'preSetRevision']);
    }
}
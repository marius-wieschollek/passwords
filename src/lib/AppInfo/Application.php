<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\AppInfo;

use OCA\Passwords\Controller\AdminSettingsController;
use OCA\Passwords\Controller\Api\FolderApiController;
use OCA\Passwords\Controller\Api\Legacy\LegacyCategoryApiController;
use OCA\Passwords\Controller\Api\Legacy\LegacyPasswordApiController;
use OCA\Passwords\Controller\Api\Legacy\LegacyVersionApiController;
use OCA\Passwords\Controller\Api\PasswordApiController;
use OCA\Passwords\Controller\Api\ServiceApiController;
use OCA\Passwords\Controller\Api\SettingsApiController;
use OCA\Passwords\Controller\Api\ShareApiController;
use OCA\Passwords\Controller\Api\TagApiController;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Helper\Sharing\ShareUserListHelper;
use OCA\Passwords\Helper\Words\LocalWordsHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Middleware\ApiSecurityMiddleware;
use OCA\Passwords\Middleware\LegacyMiddleware;
use OCA\Passwords\Services\NotificationService;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\L10N\IFactory;

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

        $this->registerDiClasses();
        $this->registerSystemHooks();
        $this->registerInternalHooks();
        $this->registerMiddleware();
        $this->registerNotificationNotifier();
        $this->enableNightlyUpdates();
    }

    /**
     */
    protected function registerDiClasses(): void {
        $container = $this->getContainer();

        /**
         * Controllers
         */
        $this->registerController();

        /**
         * Helper
         */
        $container->registerService(LocalWordsHelper::class,
            function (IAppContainer $c) {
                return new LocalWordsHelper(
                    $c->query(IFactory::class)->get('core')->getLanguageCode()
                );
            });

        /**
         * Register Legacy Api Controller Classes
         */
        $this->registerLegacyApiControllers();
    }

    /**
     *
     */
    protected function registerMiddleware(): void {
        $container = $this->getContainer();

        $container->registerAlias('ApiSecurityMiddleware', ApiSecurityMiddleware::class);
        $container->registerMiddleware('ApiSecurityMiddleware');

        if($container->getServer()->getConfig()->getAppValue(Application::APP_NAME, 'legacy_api_enabled', true)) {
            $container->registerAlias('LegacyMiddleware', LegacyMiddleware::class);
            $container->registerMiddleware('LegacyMiddleware');
        }
    }

    /**
     *
     */
    protected function registerController(): void {
        $container = $this->getContainer();

        $container->registerAlias('AdminSettingsController', AdminSettingsController::class);
        $container->registerAlias('PasswordApiController', PasswordApiController::class);
        $container->registerAlias('SettingsApiController', SettingsApiController::class);
        $container->registerAlias('ServiceApiController', ServiceApiController::class);
        $container->registerAlias('FolderApiController', FolderApiController::class);
        $container->registerAlias('ShareApiController', ShareApiController::class);
        $container->registerAlias('TagApiController', TagApiController::class);

        $container->registerService(ShareUserListHelper::class,
            function (IAppContainer $c) {
                $server = $c->getServer();

                return new ShareUserListHelper(
                    $server->getUserSession()->getUser(),
                    $server->getShareManager(),
                    $server->getUserManager(),
                    $c->query(IGroupManager::class)
                );
            });
    }

    /**
     *
     */
    protected function registerLegacyApiControllers(): void {
        $container = $this->getContainer();

        if($container->getServer()->getConfig()->getAppValue(Application::APP_NAME, 'legacy_api_enabled', true)) {
            $container->registerAlias('LegacyVersionApiController', LegacyVersionApiController::class);
            $container->registerAlias('LegacyPasswordApiController', LegacyPasswordApiController::class);
            $container->registerAlias('LegacyCategoryApiController', LegacyCategoryApiController::class);
        }
    }

    /**
     * @throws \OCP\AppFramework\QueryException
     */
    protected function registerInternalHooks(): void {
        $container = $this->getContainer();
        /** @var HookManager $hookManager */
        $hookManager = $container->query(HookManager::class);

        $hookManager->listen(Folder::class, 'postClone', [$hookManager, 'folderPostCloneHook']);
        $hookManager->listen(Folder::class, 'preDelete', [$hookManager, 'folderPreDelete']);
        $hookManager->listen(Folder::class, 'postDelete', [$hookManager, 'folderPostDelete']);
        $hookManager->listen(Folder::class, 'preSetRevision', [$hookManager, 'folderPreSetRevision']);
        $hookManager->listen(Password::class, 'postClone', [$hookManager, 'passwordPostClone']);
        $hookManager->listen(Password::class, 'preDelete', [$hookManager, 'passwordPreDelete']);
        $hookManager->listen(Password::class, 'postDelete', [$hookManager, 'passwordPostDelete']);
        $hookManager->listen(Password::class, 'preSetRevision', [$hookManager, 'passwordPreSetRevision']);
        $hookManager->listen(Tag::class, 'postClone', [$hookManager, 'tagPostClone']);
        $hookManager->listen(Tag::class, 'preDelete', [$hookManager, 'tagPreDelete']);
        $hookManager->listen(Tag::class, 'postDelete', [$hookManager, 'tagPostDelete']);
        $hookManager->listen(Tag::class, 'preSetRevision', [$hookManager, 'tagPreSetRevision']);
        $hookManager->listen(Share::class, 'postDelete', [$hookManager, 'sharePostDelete']);
    }

    /**
     * @throws \OCP\AppFramework\QueryException
     */
    protected function registerSystemHooks(): void {
        $container = $this->getContainer();
        /** @var HookManager $hookManager */
        $hookManager = $container->query(HookManager::class);
        /** @var \OC\User\Manager $userManager */
        $userManager = $container->query(IUserManager::class);

        $userManager->listen('\OC\User', 'preCreateUser', [$hookManager, 'userPreCreateUser']);
        $userManager->listen('\OC\User', 'postDelete', [$hookManager, 'userPostDelete']);
    }

    /**
     *
     */
    protected function registerNotificationNotifier(): void {
        $this->getContainer()->getServer()->getNotificationManager()->registerNotifier(
            function () {
                return $this->getContainer()->query(NotificationService::class);
            },
            function () {
                $l = $this->getContainer()->query(IL10N::class);

                return ['id' => self::APP_NAME, 'name' => $l->t('Passwords'),];
            }
        );
    }

    /**
     *
     */
    protected function enableNightlyUpdates(): void {
        $config = $this->getContainer()->getServer()->getConfig();

        if($config->getAppValue(Application::APP_NAME, 'nightly_updates', false) &&
            !class_exists('\OC\App\AppStore\Fetcher\AppFetcher', false)) {
            $version = explode('.', $config->getSystemValue('version'), 2)[0];

            require_once __DIR__.'/../Plugins/'.$version.'/NighltyAppFetcher.php';
        }
    }
}
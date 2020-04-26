<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\AppInfo;

use OC\User\Manager;
use OCA\Passwords\Controller\Admin\CacheController;
use OCA\Passwords\Controller\Admin\SettingsController;
use OCA\Passwords\Controller\Api\AccountApiController;
use OCA\Passwords\Controller\Api\FolderApiController;
use OCA\Passwords\Controller\Api\KeychainApiController;
use OCA\Passwords\Controller\Api\Legacy\LegacyCategoryApiController;
use OCA\Passwords\Controller\Api\Legacy\LegacyPasswordApiController;
use OCA\Passwords\Controller\Api\Legacy\LegacyVersionApiController;
use OCA\Passwords\Controller\Api\PasswordApiController;
use OCA\Passwords\Controller\Api\ServiceApiController;
use OCA\Passwords\Controller\Api\SessionApiController;
use OCA\Passwords\Controller\Api\SettingsApiController;
use OCA\Passwords\Controller\Api\ShareApiController;
use OCA\Passwords\Controller\Api\TagApiController;
use OCA\Passwords\Controller\Link\ConnectController;
use OCA\Passwords\Db\Challenge;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Helper\Sharing\ShareUserListHelper;
use OCA\Passwords\Helper\Words\LeipzigCorporaHelper;
use OCA\Passwords\Helper\Words\LocalWordsHelper;
use OCA\Passwords\Helper\Words\RandomCharactersHelper;
use OCA\Passwords\Helper\Words\SpecialCharacterHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Middleware\ApiSecurityMiddleware;
use OCA\Passwords\Middleware\ApiSessionMiddleware;
use OCA\Passwords\Middleware\LegacyMiddleware;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\NotificationService;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;
use OCP\Http\Client\IClientService;
use OCP\IGroupManager;
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
     * @throws QueryException
     */
    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_NAME, $urlParams);

        $this->registerDiClasses();
        $this->registerSystemHooks();
        $this->registerInternalHooks();
        $this->registerMiddleware();
        $this->registerNotificationNotifier();
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
                    $c->query(SpecialCharacterHelper::class),
                    $c->query(IFactory::class)->get('core')->getLanguageCode()
                );
            });

        $container->registerService(RandomCharactersHelper::class,
            function (IAppContainer $c) {
                return new RandomCharactersHelper(
                    $c->query(IFactory::class)->get('core')->getLanguageCode()
                );
            });

        $container->registerService(LeipzigCorporaHelper::class,
            function (IAppContainer $c) {
                return new LeipzigCorporaHelper(
                    $c->query(SpecialCharacterHelper::class),
                    $c->query(IClientService::class),
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

        $container->registerAlias('ApiSessionMiddleware', ApiSessionMiddleware::class);
        $container->registerMiddleware('ApiSessionMiddleware');

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

        $container->registerAlias('AdminSettingsController', SettingsController::class);
        $container->registerAlias('AdminCachesController', CacheController::class);
        $container->registerAlias('KeychainApiController', KeychainApiController::class);
        $container->registerAlias('PasswordApiController', PasswordApiController::class);
        $container->registerAlias('SettingsApiController', SettingsApiController::class);
        $container->registerAlias('AccountApiController', AccountApiController::class);
        $container->registerAlias('SessionApiController', SessionApiController::class);
        $container->registerAlias('ServiceApiController', ServiceApiController::class);
        $container->registerAlias('FolderApiController', FolderApiController::class);
        $container->registerAlias('ShareApiController', ShareApiController::class);
        $container->registerAlias('ConnectController', ConnectController::class);
        $container->registerAlias('TagApiController', TagApiController::class);

        $container->registerService(ShareUserListHelper::class,
            function (IAppContainer $c) {
                $server = $c->getServer();

                return new ShareUserListHelper(
                    $server->getShareManager(),
                    $server->getUserManager(),
                    $c->query(IGroupManager::class),
                    $c->query(ConfigurationService::class),
                    $c->query(EnvironmentService::class)
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
     * @throws QueryException
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
        $hookManager->listen(Challenge::class, 'preSetChallenge', [$hookManager, 'challengePreSetChallenge']);
        $hookManager->listen(Challenge::class, 'postSetChallenge', [$hookManager, 'challengePostSetChallenge']);
    }

    /**
     * @throws QueryException
     */
    protected function registerSystemHooks(): void {
        $container = $this->getContainer();
        /** @var HookManager $hookManager */
        $hookManager = $container->query(HookManager::class);
        /** @var Manager $userManager */
        $userManager = $container->query(IUserManager::class);

        $userManager->listen('\OC\User', 'preCreateUser', [$hookManager, 'userPreCreateUser']);
        $userManager->listen('\OC\User', 'postDelete', [$hookManager, 'userPostDelete']);
    }

    /**
     * Registers the Notification service
     */
    protected function registerNotificationNotifier(): void {
        $container = $this->getContainer();
        $server    = $container->getServer();

        $server->getNotificationManager()->registerNotifierService(NotificationService::class);
    }
}
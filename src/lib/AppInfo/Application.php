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
use OCA\Passwords\EventListener\Password\BeforePasswordDeletedListener;
use OCA\Passwords\EventListener\Password\BeforePasswordSetRevisionListener;
use OCA\Passwords\EventListener\Password\PasswordClonedListener;
use OCA\Passwords\EventListener\Password\PasswordDeletedListener;
use OCA\Passwords\Events\Password\BeforePasswordDeletedEvent;
use OCA\Passwords\Events\Password\BeforePasswordSetRevisionEvent;
use OCA\Passwords\Events\Password\PasswordClonedEvent;
use OCA\Passwords\Events\Password\PasswordDeletedEvent;
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
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Share\IManager as ShareManager;

/**
 * Class Application
 *
 * @package OCA\Passwords\AppInfo
 */
class Application extends App implements IBootstrap {

    const APP_NAME = 'passwords';

    /**
     * Application constructor.
     *
     * @param array $urlParams
     */
    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_NAME, $urlParams);
    }

    /**
     * @param IRegistrationContext $context
     */
    public function register(IRegistrationContext $context): void {
        $this->registerDiClasses($context);
        $this->registerSystemHooks();
        $this->registerInternalHooks();
        $this->registerMiddleware($context);
    }

    /**
     * @param IBootContext $context
     */
    public function boot(IBootContext $context): void {
        $this->registerNotificationNotifier();
        $this->registerInternalListeners();
    }

    /**
     * @param IRegistrationContext $context
     */
    protected function registerDiClasses(IRegistrationContext $context): void {
        /**
         * Controllers
         */
        $this->registerController($context);

        /**
         * Helper
         */
        $context->registerService(LocalWordsHelper::class,
            function (IAppContainer $c) {
                return new LocalWordsHelper(
                    $c->get(SpecialCharacterHelper::class),
                    $c->get(IFactory::class)->get('core')->getLanguageCode()
                );
            });

        $context->registerService(RandomCharactersHelper::class,
            function (IAppContainer $c) {
                return new RandomCharactersHelper(
                    $c->get(IFactory::class)->get('core')->getLanguageCode()
                );
            });

        $context->registerService(LeipzigCorporaHelper::class,
            function (IAppContainer $c) {
                return new LeipzigCorporaHelper(
                    $c->get(SpecialCharacterHelper::class),
                    $c->get(IClientService::class),
                    $c->get(IFactory::class)->get('core')->getLanguageCode()
                );
            });

        /**
         * Register Legacy Api Controller Classes
         */
        $this->registerLegacyApiControllers($context);
    }

    /**
     * @param IRegistrationContext $context
     */
    protected function registerMiddleware(IRegistrationContext $context): void {
        $context->registerServiceAlias('ApiSecurityMiddleware', ApiSecurityMiddleware::class);
        $context->registerMiddleware('ApiSecurityMiddleware');

        $context->registerServiceAlias('ApiSessionMiddleware', ApiSessionMiddleware::class);
        $context->registerMiddleware('ApiSessionMiddleware');

        if($this->getContainer()->get(IConfig::class)->getAppValue(Application::APP_NAME, 'legacy_api_enabled', true)) {
            $context->registerServiceAlias('LegacyMiddleware', LegacyMiddleware::class);
            $context->registerMiddleware('LegacyMiddleware');
        }
    }

    /**
     * @param IRegistrationContext $context
     */
    protected function registerController(IRegistrationContext $context): void {
        $context->registerServiceAlias('AdminSettingsController', SettingsController::class);
        $context->registerServiceAlias('AdminCachesController', CacheController::class);
        $context->registerServiceAlias('KeychainApiController', KeychainApiController::class);
        $context->registerServiceAlias('PasswordApiController', PasswordApiController::class);
        $context->registerServiceAlias('SettingsApiController', SettingsApiController::class);
        $context->registerServiceAlias('AccountApiController', AccountApiController::class);
        $context->registerServiceAlias('SessionApiController', SessionApiController::class);
        $context->registerServiceAlias('ServiceApiController', ServiceApiController::class);
        $context->registerServiceAlias('FolderApiController', FolderApiController::class);
        $context->registerServiceAlias('ShareApiController', ShareApiController::class);
        $context->registerServiceAlias('ConnectController', ConnectController::class);
        $context->registerServiceAlias('TagApiController', TagApiController::class);

        $context->registerService(ShareUserListHelper::class,
            function (IAppContainer $c) {
                return new ShareUserListHelper(
                    $c->get(ShareManager::class),
                    $c->get(IUserManager::class),
                    $c->get(IGroupManager::class),
                    $c->get(ConfigurationService::class),
                    $c->get(EnvironmentService::class)
                );
            });
    }

    /**
     * @param IRegistrationContext $context
     */
    protected function registerLegacyApiControllers(IRegistrationContext $context): void {
        if($this->getContainer()->get(IConfig::class)->getAppValue(Application::APP_NAME, 'legacy_api_enabled', true)) {
            $context->registerServiceAlias('LegacyVersionApiController', LegacyVersionApiController::class);
            $context->registerServiceAlias('LegacyPasswordApiController', LegacyPasswordApiController::class);
            $context->registerServiceAlias('LegacyCategoryApiController', LegacyCategoryApiController::class);
        }
    }

    /**
     *
     */
    protected function registerInternalHooks(): void {
        $container = $this->getContainer();
        /** @var HookManager $hookManager */
        $hookManager = $container->get(HookManager::class);

        $hookManager->listen(Folder::class, 'postClone', [$hookManager, 'folderPostCloneHook']);
        $hookManager->listen(Folder::class, 'preDelete', [$hookManager, 'folderPreDelete']);
        $hookManager->listen(Folder::class, 'postDelete', [$hookManager, 'folderPostDelete']);
        $hookManager->listen(Folder::class, 'preSetRevision', [$hookManager, 'folderPreSetRevision']);
        $hookManager->listen(Tag::class, 'postClone', [$hookManager, 'tagPostClone']);
        $hookManager->listen(Tag::class, 'preDelete', [$hookManager, 'tagPreDelete']);
        $hookManager->listen(Tag::class, 'postDelete', [$hookManager, 'tagPostDelete']);
        $hookManager->listen(Tag::class, 'preSetRevision', [$hookManager, 'tagPreSetRevision']);
        $hookManager->listen(Share::class, 'postDelete', [$hookManager, 'sharePostDelete']);
        $hookManager->listen(Challenge::class, 'preSetChallenge', [$hookManager, 'challengePreSetChallenge']);
        $hookManager->listen(Challenge::class, 'postSetChallenge', [$hookManager, 'challengePostSetChallenge']);
    }

    /**
     *
     */
    protected function registerInternalListeners() {
        /* @var IEventDispatcher $eventDispatcher */
        $dispatcher = $this->getContainer()->get(IEventDispatcher::class);
        $dispatcher->addServiceListener(BeforePasswordDeletedEvent::class, BeforePasswordDeletedListener::class);
        $dispatcher->addServiceListener(BeforePasswordSetRevisionEvent::class, BeforePasswordSetRevisionListener::class);
        $dispatcher->addServiceListener(PasswordClonedEvent::class, PasswordClonedListener::class);
        $dispatcher->addServiceListener(PasswordDeletedEvent::class, PasswordDeletedListener::class);
    }

    /**
     *
     */
    protected function registerSystemHooks(): void {
        $container = $this->getContainer();
        /** @var HookManager $hookManager */
        $hookManager = $container->get(HookManager::class);
        /** @var Manager $userManager */
        $userManager = $container->get(IUserManager::class);

        $userManager->listen('\OC\User', 'preCreateUser', [$hookManager, 'userPreCreateUser']);
        $userManager->listen('\OC\User', 'postDelete', [$hookManager, 'userPostDelete']);
    }

    /**
     * Registers the Notification service
     */
    protected function registerNotificationNotifier(): void {
        $this->getContainer()->get(IManager::class)->registerNotifierService(NotificationService::class);
    }
}
<?php
/*
 * @copyright 2020 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\AppInfo;

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
use OCA\Passwords\EventListener\Challenge\ChallengeActivatedListener;
use OCA\Passwords\EventListener\Folder\BeforeFolderDeletedListener;
use OCA\Passwords\EventListener\Folder\BeforeFolderSetRevisionListener;
use OCA\Passwords\EventListener\Folder\FolderClonedListener;
use OCA\Passwords\EventListener\Folder\FolderDeletedListener;
use OCA\Passwords\EventListener\Password\BeforePasswordDeletedListener;
use OCA\Passwords\EventListener\Password\BeforePasswordSetRevisionListener;
use OCA\Passwords\EventListener\Password\PasswordClonedListener;
use OCA\Passwords\EventListener\Password\PasswordDeletedListener;
use OCA\Passwords\EventListener\Share\ShareDeletedListener;
use OCA\Passwords\EventListener\Tag\BeforeTagDeletedListener;
use OCA\Passwords\EventListener\Tag\BeforeTagSetRevisionListener;
use OCA\Passwords\EventListener\Tag\TagClonedListener;
use OCA\Passwords\EventListener\Tag\TagDeletedListener;
use OCA\Passwords\EventListener\User\BeforeUserCreatedListener;
use OCA\Passwords\EventListener\User\UserDeletedListener;
use OCA\Passwords\Events\Challenge\BeforeChallengeActivatedEvent;
use OCA\Passwords\Events\Challenge\ChallengeActivatedEvent;
use OCA\Passwords\Events\Folder\BeforeFolderDeletedEvent;
use OCA\Passwords\Events\Folder\BeforeFolderSetRevisionEvent;
use OCA\Passwords\Events\Folder\FolderClonedEvent;
use OCA\Passwords\Events\Folder\FolderDeletedEvent;
use OCA\Passwords\Events\Password\BeforePasswordDeletedEvent;
use OCA\Passwords\Events\Password\BeforePasswordSetRevisionEvent;
use OCA\Passwords\Events\Password\PasswordClonedEvent;
use OCA\Passwords\Events\Password\PasswordDeletedEvent;
use OCA\Passwords\Events\Share\ShareDeletedEvent;
use OCA\Passwords\Events\Tag\BeforeTagDeletedEvent;
use OCA\Passwords\Events\Tag\BeforeTagSetRevisionEvent;
use OCA\Passwords\Events\Tag\TagClonedEvent;
use OCA\Passwords\Events\Tag\TagDeletedEvent;
use OCA\Passwords\Helper\Sharing\ShareUserListHelper;
use OCA\Passwords\Helper\Words\LeipzigCorporaHelper;
use OCA\Passwords\Helper\Words\LocalWordsHelper;
use OCA\Passwords\Helper\Words\RandomCharactersHelper;
use OCA\Passwords\Helper\Words\SpecialCharacterHelper;
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
use OCP\User\Events\BeforeUserCreatedEvent;
use OCP\User\Events\CreateUserEvent;
use OCP\User\Events\UserDeletedEvent;

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
    protected function registerInternalListeners() {
        /* @var IEventDispatcher $eventDispatcher */
        $dispatcher = $this->getContainer()->get(IEventDispatcher::class);
        $dispatcher->addServiceListener(BeforePasswordDeletedEvent::class, BeforePasswordDeletedListener::class);
        $dispatcher->addServiceListener(BeforePasswordSetRevisionEvent::class, BeforePasswordSetRevisionListener::class);
        $dispatcher->addServiceListener(PasswordClonedEvent::class, PasswordClonedListener::class);
        $dispatcher->addServiceListener(PasswordDeletedEvent::class, PasswordDeletedListener::class);

        $dispatcher->addServiceListener(BeforeFolderDeletedEvent::class, BeforeFolderDeletedListener::class);
        $dispatcher->addServiceListener(BeforeFolderSetRevisionEvent::class, BeforeFolderSetRevisionListener::class);
        $dispatcher->addServiceListener(FolderClonedEvent::class, FolderClonedListener::class);
        $dispatcher->addServiceListener(FolderDeletedEvent::class, FolderDeletedListener::class);

        $dispatcher->addServiceListener(BeforeTagDeletedEvent::class, BeforeTagDeletedListener::class);
        $dispatcher->addServiceListener(BeforeTagSetRevisionEvent::class, BeforeTagSetRevisionListener::class);
        $dispatcher->addServiceListener(TagClonedEvent::class, TagClonedListener::class);
        $dispatcher->addServiceListener(TagDeletedEvent::class, TagDeletedListener::class);

        $dispatcher->addServiceListener(ShareDeletedEvent::class, ShareDeletedListener::class);

        $dispatcher->addServiceListener(BeforeChallengeActivatedEvent::class, ChallengeActivatedListener::class);
        $dispatcher->addServiceListener(ChallengeActivatedEvent::class, ChallengeActivatedListener::class);
    }

    /**
     *
     */
    protected function registerSystemHooks(): void {
        /* @var IEventDispatcher $eventDispatcher */
        $dispatcher = $this->getContainer()->get(IEventDispatcher::class);
        $dispatcher->addServiceListener(CreateUserEvent::class, BeforeUserCreatedListener::class);
        $dispatcher->addServiceListener(BeforeUserCreatedEvent::class, BeforeUserCreatedListener::class);
        $dispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedListener::class);
    }

    /**
     * Registers the Notification service
     */
    protected function registerNotificationNotifier(): void {
        $this->getContainer()->get(IManager::class)->registerNotifierService(NotificationService::class);
    }
}